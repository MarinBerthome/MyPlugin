<?php
/**
 * Fonction d'installation du plugin
 * @return boolean
 */

function plugin_dashboardcredit_install()
{
    global $DB;

    if($DB->tableExists('glpi_dashboards_dashboards'))
    {
        $query = "INSERT INTO `glpi_dashboards_dashboards` 
          (`id`,
           `key`, 
           `name`, 
           `context`) 
           VALUES 
           ('6', 
           'test', 
           'Test', 
           'core');";
        $DB->query($query);

    }
    return true ;
}

function plugin_dashboardcredit_uninstall()
{
    global $DB;

    if($DB->tableExists('glpi_dashboards_dashboards'))
    {
        $query = "DELETE FROM `glpi_dashboards_dashboards` 
        WHERE `glpi_dashboards_dashboards`.`key` = 'test' ;";
        $DB->query($query);

    }
    return true ;
}


function plugin_dashboardcredit_dashboardCards()
{
    $cards = [];
    $cards = array_merge($cards, CreditCards::dashboardCards());

    return $cards;
}
