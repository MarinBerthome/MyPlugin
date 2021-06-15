<?php
/**
 * Fonction d'installation du plugin
 * @return boolean
 */

/**
 * Installation du plug-in
 * On va créer un nouveau tableau de bord crédit
 */
function plugin_morewidgets_install(): bool
{
    global $DB;

    if($DB->tableExists('glpi_dashboards_dashboards'))
    {
        $query = "INSERT INTO `glpi_dashboards_dashboards` 
          (`key`, 
           `name`, 
           `context`) 
           VALUES 
           ('Credits', 
           'Credits', 
           'core');";
        $DB->query($query);

    }
    return true ;
}

/**
 * Fonction appelée lors de de la desinstallation du plug-in.
 */
function plugin_morewidgets_uninstall(): bool
{
    global $DB;

    if($DB->tableExists('glpi_dashboards_dashboards'))
    {
        $query = "DELETE FROM `glpi_dashboards_dashboards` 
        WHERE `glpi_dashboards_dashboards`.`key` = 'Credits' ;";
        $DB->query($query);

    }
    return true ;
}

/**
 * Retourne les nouvelles cartes ajoutées par le plug-in
 */
function plugin_morewidgets_dashboardCards(): array
{
    $cards = [];
    return array_merge($cards, PluginMorewidgetsCreditcards::creditCards()) +  array_merge($cards, PluginMorewidgetsAssistancecards::assistanceCards());
}

/**
 * Retourne les cérdits ajoutés par le plug-in
 * Attention : Il faut ajouter une fonction correspondante. Voir Documentation Plug-in.
 */
function plugin_morewidgets_filter(): array
{
    return [
        'credit'      => __("Type de crédit"),
        'sla'         => __("SLAs"),
    ];
}
