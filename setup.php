<?php


function plugin_init_dashboardcredit() {

    global $PLUGIN_HOOKS, $LANG ;

    $PLUGIN_HOOKS['csrf_compliant']['dashboardcredit'] = true;

    Plugin::registerClass('PluginDashboardConfig', [
        'addtabon' => ['Entity']
    ]);

    $PLUGIN_HOOKS["menu_toadd"]['dashboardcredit'] = array('plugins'  => 'PluginDashboardConfig');
    $PLUGIN_HOOKS['config_page']['dashboardcredit'] = 'front/index.php';


    // hook on dashboard (cards, providers, widgets)
    $PLUGIN_HOOKS['dashboard_cards']['dashboardcredit'] = 'plugin_dashboardcredit_dashboardCards';

}


function plugin_version_dashboardcredit(){
    global $DB, $LANG;

    return array('name'			=> __('Dashboard Credit','dashboardcredit'),
        'version' 			=> '1.0.2',
        'author'			   => 'Maribert',
        'license'		 	=> 'GPLv2+',
        'homepage'			=> 'https://forge.glpi-project.org/projects/dashboard',
        'minGlpiVersion'	=> '9.4'
    );
}


function plugin_dashboardcredit_check_prerequisites(){
    if (GLPI_VERSION >= 9.4){
        return true;
    } else {
        echo "GLPI version NOT compatible. Requires GLPI >= 9.4";
    }
}


function plugin_dashboardcredit_check_config($verbose=false){
    if ($verbose) {
        echo 'Installed / not configured';
    }
    return true;
}


?>
