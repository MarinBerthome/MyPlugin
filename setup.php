<?php

/**
 * Initialisation du plugin, c'est ici qu'on retrouve les hooks
 */
function plugin_init_morewidgets() {

    global $PLUGIN_HOOKS, $LANG ;

    $PLUGIN_HOOKS['csrf_compliant']['morewidgets'] = true;

    Plugin::registerClass('PluginDashboardConfig', [
        'addtabon' => ['Entity']
    ]);

    // requis pour tous les plugins
    $PLUGIN_HOOKS["menu_toadd"]['morewidgets'] = array('plugins'  => 'PluginDashboardConfig');
    $PLUGIN_HOOKS['config_page']['morewidgets'] = 'front/index.php';


    // hook on dashboard (cards, providers, widgets)
    $PLUGIN_HOOKS['dashboard_cards']['morewidgets'] = 'plugin_morewidgets_dashboardCards';

    // hook on filters
    $PLUGIN_HOOKS['dashboard_filters']['morewidgets'] = 'plugin_morewidgets_filter';

}

/**
 * Informations sur le plugin
 * @return array
 */
function plugin_version_morewidgets(){
    global $DB, $LANG;

    return array('name'			=> __('More Widgets','morewidgets'),
        'version' 			=> '1.0.2',
        'author'			   => 'IT Gouvernance',
        'license'		 	=> 'GPLv2+',
        'homepage'			=> 'https://forge.glpi-project.org/projects/dashboard',
        'minGlpiVersion'	=> '9.4'
    );
}

/**
 * Vérifie si la version de GLPI est bien compatible
 * @return bool
 */
function plugin_morewidgets_check_prerequisites(){
    if (GLPI_VERSION >= 9.4){
        return true;
    } else {
        echo "GLPI version NOT compatible. Requires GLPI >= 9.4";
    }
}

function plugin_morewidgets_check_config($verbose=false){
    if ($verbose) {
        echo 'Installed / not configured';
    }
    return true;
}



