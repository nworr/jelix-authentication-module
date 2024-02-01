<?php
/**
 * @author alagroy
 * @copyright 2021-2024 Laurent Jouanneau and other contributors
 * @license    MIT
 */
namespace Jelix\Authentication\Account;

use jAuthentication;
use Jelix\Authentication\Core\AuthSession\AuthUser;
use Jelix\Authentication\Core\IdentityProviderInterface;

class Manager
{
    protected static $daoName = 'account~accounts';

    protected static $daoIdp = 'account~account_idp';

    protected static $daoProfile = 'account';

    const STATUS_VALID = 1;
    const STATUS_INVALID = 0;
    const STATUS_DISABLED = -1;
    const STATUS_DELETED = -2;

    /**
     * Retrieves a registered account from the Dao.
     * 
     * @param string $username The account's username
     * 
     * @return Account An Account object containing the account's infos or null if the account doesn't exist.
     */
    public static function getAccount($username)
    {
        $dao = \jDao::get(self::$daoName, self::$daoProfile);

        $record = $dao->findByUserName($username);
        if (!$record) {
            return null;
        }
        return new Account($record);
    }

    /**
     * Creates an account object
     *
     * @return \jDaoRecordBase The record containing the account
     */
    public static function createAccountObject($userName, $email)
    {
        $newAccount = \jDao::createRecord(self::$daoName, self::$daoProfile);
        $newAccount->username = $userName;
        $newAccount->email = $email;
        $newAccount->status = self::STATUS_VALID;
        return $newAccount;
    }

    public static function saveNewAccount($newAccount)
    {
        $dao = \jDao::get(self::$daoName, self::$daoProfile);
        $dao->insert($newAccount);
    }


    /**
     * Creates a new account from the authenticated user
     * 
     * @param AuthUser $user The user to create
     * @param IdentityProviderInterface $provider The IdentityProvider used to create the Account
     * 
     * @return \jDaoRecordBase The record containing the new account or null if account already exists.
     */
    public static function createAccountFromAuthUser($user, $provider, $status = null)
    {
        $name = $user->getLogin();
        if (self::accountExists($name)) {
            return null;
        }

        if ($status === null) {
            $status = self::STATUS_VALID;
        }

        $dao = \jDao::get(self::$daoName, self::$daoProfile);
        $newAccount = \jDao::createRecord(self::$daoName, self::$daoProfile);
        $newAccount->name = $name;
        $newAccount->email = $user->getEmail();
        $newAccount->status = $status;
        $dao->insert($newAccount);
        return $newAccount;
    }

    /**
     * Checks whether an account exists for the given name
     * 
     * @param string $username The name to check
     * @return bool true if account exists
     */
    public static function accountExists($username)
    {
        if (self::getAccount($username)) {
            return true;
        }
        return false;
    }

    /**
     * @return Account|null
     */
    public static function getCurrentUserAccount()
    {
        $user = jAuthentication::getCurrentUser();
        if (!$user) {
            return null;
        }
        return self::getAccount($user->getLogin());
    }

    /**
     * Modifies the information of a user
     * 
     * @param array $newInfos An associative array containing the information to modify and their new values
     * @param Account $user the user to modify, if null, the modified user will be the current one.
     * 
 * @return Account|false False if an error occurred, else a new Account object wih the updated information
     */
    public static function modifyInfos($newInfos, $accountId)
    {
        $dao = \jDao::get(self::$daoName, self::$daoProfile);
        $record = $dao->get($accountId);
        if (!$record) {
            return false;
        }

        foreach ($newInfos as $prop => $value) {
            $record->$prop = $value;
        }

        $dao->update($record);
        return new Account($record);
    }

    public static function getAccountList()
    {
        return \jDao::get(self::$daoName, self::$daoProfile)->findAll();
    }

    /**
     * @param string $idpId
     * @param string $authUserId
     * @return null|Account
     */
    public static function searchAccountByIdp($idpId, $authUserId)
    {
        $daoIdp = \jDao::get(self::$daoIdp, self::$daoProfile);
        $accIdp = $daoIdp->findByIdpAndUser($idpId, $authUserId);
        if (!$accIdp) {
            \jLog::log('no account-idp for '.$idpId. ' - '. $authUserId);
            return null;
        }

        $dao = \jDao::get(self::$daoName, self::$daoProfile);

        $record = $dao->get($accIdp->account_id);
        if (!$record) {
            \jLog::log('no account for account '.$accIdp->username);
            return null;
        }
        \jLog::log('account found for '.$authUserId);
        return new Account($record);
    }

    /**
     * @param integer $accountId
     * @return null|Account
     */
    public static function searchIdpUsedByAccount($accountId)
    {
        $daoIdp = \jDao::get(self::$daoIdp, self::$daoProfile);
        $list = $daoIdp->findByAccount($accountId);
        return $list;
    }

    /**
     * @param integer $accountId
     * @param string $idpId
     * @return bool
     */
    public static function isAccountUsingIdp($accountId, $idpId)
    {
        $daoIdp = \jDao::get(self::$daoIdp, self::$daoProfile);
        $accIdp = $daoIdp->findByIdpAndAccount($idpId, $accountId);
        if ($accIdp) {
            return true;
        }
        return false;
    }

    /**
     * @param integer $accountId
     * @param string $idpId
     * @param string $authUserId
     * @param string $authUserEmail
     * @param object|array $idpData
     * @return void
     */
    public static function attachAccountToIdp($accountId, $idpId, $authUserId, $authUserEmail, $idpData = [])
    {
        $daoIdp = \jDao::get(self::$daoIdp, self::$daoProfile);
        $recordIdp = $daoIdp->createRecord();
        $recordIdp->idp_id = $idpId;
        $recordIdp->idp_user_id = $authUserId;
        $recordIdp->account_id = $accountId;
        $recordIdp->idp_user_email = $authUserEmail;
        $recordIdp->enabled = true;
        $recordIdp->idp_data = json_encode($idpData);
        $daoIdp->insert($recordIdp);
    }

    /**
     * @param integer $accountId
     * @param string $idpId
     * @param string $authUserId
     * @return void
     * @throws \jException
     */
    public static function detachAccountFromIdp($accountId, $idpId, $authUserId)
    {
        $daoIdp = \jDao::get(self::$daoIdp, self::$daoProfile);
        $daoIdp->delete([$idpId, $authUserId, $accountId]);
    }

}