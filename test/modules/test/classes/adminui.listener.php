<?php
/**
 * @author   Laurent Jouanneau
 * @copyright 2019 Laurent Jouanneau
 * @link     http://jelix.org
 * @licence MIT
 */

use Jelix\AdminUI\Dashboard\HtmlBox;
use Jelix\AdminUI\Dashboard\SmallBox;
use Jelix\AdminUI\Dashboard\SmallBox2;
use Jelix\AdminUI\Link;
use Jelix\AdminUI\SideBar\SubMenu;
use Jelix\AdminUI\SideBar\LinkMenuItem;
use Jelix\AdminUI\ControlSideBar\Panel;

class adminuiListener extends jEventListener
{

    protected $eventMapping = array(
        'adminui.loading' => 'onAdminUILoading',
        'adminui.dashboard.loading' => 'onDashboardLoading'
    );

    /**
     * @param jEvent $event
     */
    function onAdminUILoading($event) {
        /** @var \Jelix\AdminUI\UIManager $uim */
        $uim = $event->uiManager;

        $accountMenu = $uim->navbar()->accountMenu();
        //$accountMenu->setNotAuthenticated('#signin');
        $accountMenu->setAuthenticated('laurentj', 'Laurent Jouanneau', jUrl::get('test~default:login'), '#profile');
        //$accountMenu->setAuthenticated('laurentj', 'Laurent Jouanneau', '#signout', '#profile', \jApp::urlBasePath().'adminlte-assets/dist/img/user2-160x160.jpg');
        //$accountMenu->addLink(new Link('#prefs', 'Your preferences'));

        $navigation = new SubMenu('nav', 'Navigation', 10);
        $navigation->addJelixLinkItem('index test', 'test~default:index', array(), 'circle-o');
        $uim->sidebar()->addMenuItem($navigation);
    }

    /**
     * @param jEvent $event
     */
    function onDashboardLoading($event) {

    }
}