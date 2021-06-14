<?php
/**
 * Fonction d'installation du plugin
 * @return boolean
 */

function plugin_morewidgets_install()
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

function plugin_morewidgets_uninstall()
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


function plugin_morewidgets_dashboardCards()
{
    $cards = [];
    $cards = array_merge($cards, PluginMorewidgetsCreditcards::creditCards());
    $cards += array_merge($cards, PluginMorewidgetsAssistancecards::assistanceCards());

    return $cards;
}

function plugin_morewidgets_filter()
{
    $filter = [
        'credit'      => __("Type de crédit"),
        'sla'         => __("SLAs"),
    ];

    return $filter;
}
