<?php


class PluginMorewidgetsUtilities extends CommonDBTM
{
    public static function getFiltersCriteria(string $table = "", array $apply_filters = [])
    {
        $DB = DBConnection::getReadConnection();

        $where = [];
        $join = [];

        if ($DB->fieldExists($table, 'id')
            && isset($apply_filters['credit'])
            && (int)$apply_filters['credit'] > 0) {
            $where += [
                "$table.id" => (int)$apply_filters['credit']
            ];
        }

        if (($DB->fieldExists($table, 'date'))
            && isset($apply_filters['dates'])
            && count($apply_filters['dates']) == 2) {
            $where += self::getDatesCriteria("$table.date", $apply_filters['dates']);
        }

        //exclude itilobject already processed with 'date'
        if ((!in_array($table, [
                    Ticket::getTable(),
                    Change::getTable(),
                    Problem::getTable(),
                ]) && $DB->fieldExists($table, 'date_creation'))
            && isset($apply_filters['dates'])
            && count($apply_filters['dates']) == 2) {
            $where += self::getDatesCriteria("$table.date_creation", $apply_filters['dates']);
        }

        if ($DB->fieldExists($table, 'date_mod')
            && isset($apply_filters['dates_mod'])
            && count($apply_filters['dates_mod']) == 2) {
            $where += self::getDatesCriteria("$table.date_mod", $apply_filters['dates_mod']);
        }

        if ($DB->fieldExists($table, 'slas_id_ttr')
            && isset($apply_filters['sla'])
            && (int)$apply_filters['sla'] > 0) {
            $where += [
                "$table.slas_id_ttr" => (int)$apply_filters['sla']
            ];
        }

        if ($DB->fieldExists($table, 'itilcategories_id')
            && isset($apply_filters['itilcategory'])
            && (int)$apply_filters['itilcategory'] > 0) {
            $where += [
                "$table.itilcategories_id" => (int)$apply_filters['itilcategory']
            ];
        }

        if ($DB->fieldExists($table, 'requesttypes_id')
            && isset($apply_filters['requesttype'])
            && (int)$apply_filters['requesttype'] > 0) {
            $where += [
                "$table.requesttypes_id" => (int)$apply_filters['requesttype']
            ];
        }

        if (isset($apply_filters['group_tech'])) {

            $groups_id = null;
            if ((int)$apply_filters['group_tech'] > 0) {
                $groups_id = (int)$apply_filters['group_tech'];
            } else if ((int)$apply_filters['group_tech'] == -1) {
                $groups_id = $_SESSION['glpigroups'];
            }

            if ($groups_id != null) {
                if ($DB->fieldExists($table, 'groups_id_tech')) {
                    $where += [
                        "$table.groups_id_tech" => $groups_id
                    ];
                } else if (in_array($table, [
                    Ticket::getTable(),
                    Change::getTable(),
                    Problem::getTable(),
                ])) {
                    $itemtype = getItemTypeForTable($table);
                    $main_item = getItemForItemtype($itemtype);
                    $grouplink = $main_item->grouplinkclass;
                    $gl_table = $grouplink::getTable();
                    $fk = $main_item->getForeignKeyField();

                    $join += [
                        "$gl_table as gl" => [
                            'ON' => [
                                'gl' => $fk,
                                $table => 'id',
                            ]
                        ]
                    ];
                    $where += [
                        "gl.type" => \CommonITILActor::ASSIGN,
                        "gl.groups_id" => $groups_id
                    ];
                }
            }
        }

        if (isset($apply_filters['user_tech'])
            && (int)$apply_filters['user_tech'] > 0) {

            if ($DB->fieldExists($table, 'users_id_tech')) {
                $where += [
                    "$table.users_id_tech" => (int)$apply_filters['user_tech']
                ];
            }
        }


        $criteria = [];

        if (count($where)) {
            $criteria['WHERE'] = $where;
        }
        if (count($join)) {
            $criteria['LEFT JOIN'] = $join;
        }

        return $criteria;
    }
    public static function getSearchFiltersCriteria(string $table = "", array $apply_filters = []): array
    {
        $DB = DBConnection::getReadConnection();
        $s_criteria = [];

        if ($DB->fieldExists($table, 'date')
            && isset($apply_filters['dates'])
            && count($apply_filters['dates']) == 2) {
            $s_criteria['criteria'][] = self::getDatesSearchCriteria(self::getSearchOptionID($table, "date", $table), $apply_filters['dates'], 'begin');
            $s_criteria['criteria'][] = self::getDatesSearchCriteria(self::getSearchOptionID($table, "date", $table), $apply_filters['dates'], 'end');
        }

        //exclude itilobject already processed with 'date'
        if (!in_array($table, [
                Ticket::getTable(),
                Change::getTable(),
                Problem::getTable(),
            ]) && $DB->fieldExists($table, 'date_creation')
            && isset($apply_filters['dates'])
            && count($apply_filters['dates']) == 2) {
            $s_criteria['criteria'][] = self::getDatesSearchCriteria(self::getSearchOptionID($table, "date_creation", $table), $apply_filters['dates'], 'begin');
            $s_criteria['criteria'][] = self::getDatesSearchCriteria(self::getSearchOptionID($table, "date_creation", $table), $apply_filters['dates'], 'end');
        }



        if ($DB->fieldExists($table, 'date_mod')
            && isset($apply_filters['dates_mod'])
            && count($apply_filters['dates_mod']) == 2) {
            $s_criteria['criteria'][] = self::getDatesSearchCriteria(self::getSearchOptionID($table, "date_mod", $table), $apply_filters['dates_mod'], 'begin');
            $s_criteria['criteria'][] = self::getDatesSearchCriteria(self::getSearchOptionID($table, "date_mod", $table), $apply_filters['dates_mod'], 'end');
        }
        if ($DB->fieldExists($table, 'itilcategories_id')
            && isset($apply_filters['itilcategory'])
            && (int)$apply_filters['itilcategory'] > 0) {
            $s_criteria['criteria'][] = [
                'link' => 'AND',
                'field' => self::getSearchOptionID($table, 'itilcategories_id', 'glpi_itilcategories'), // itilcategory
                'searchtype' => 'equals',
                'value' => (int)$apply_filters['itilcategory']
            ];
        }


        if ($DB->fieldExists($table, 'slas_id_ttr')
            && isset($apply_filters['sla'])
            && (int)$apply_filters['sla'] > 0) {
            $s_criteria['criteria'][] = [
                'link'        => 'AND',
                'field'       => 30,
                'searchtype'  => 'equals',
                'value'       => (int)$apply_filters['sla'],
            ];
        }

        if ($DB->fieldExists($table, 'requesttypes_id')
            && isset($apply_filters['requesttype'])
            && (int)$apply_filters['requesttype'] > 0) {
            $s_criteria['criteria'][] = [
                'link' => 'AND',
                'field' => self::getSearchOptionID($table, 'requesttypes_id', 'glpi_requesttypes'), // request type
                'searchtype' => 'equals',
                'value' => (int)$apply_filters['requesttype']
            ];
        }


        if ($DB->fieldExists($table, 'locations_id')
            && isset($apply_filters['location'])
            && (int)$apply_filters['location'] > 0) {
            $s_criteria['criteria'][] = [
                'link' => 'AND',
                'field' => self::getSearchOptionID($table, 'locations_id', 'glpi_locations'), // location
                'searchtype' => 'equals',
                'value' => (int)$apply_filters['location']
            ];
        }

        if ($DB->fieldExists($table, 'manufacturers_id')
            && isset($apply_filters['manufacturer'])
            && (int)$apply_filters['manufacturer'] > 0) {
            $s_criteria['criteria'][] = [
                'link' => 'AND',
                'field' => self::getSearchOptionID($table, 'manufacturers_id', 'glpi_manufacturers'), // manufacturer
                'searchtype' => 'equals',
                'value' => (int)$apply_filters['manufacturer']
            ];
        }

        if (isset($apply_filters['group_tech'])) {

            $groups_id = null;
            if ((int)$apply_filters['group_tech'] > 0) {
                $groups_id = (int)$apply_filters['group_tech'];
            } else if ((int)$apply_filters['group_tech'] == -1) {
                $groups_id = 'mygroups';
            }

            if ($groups_id != null) {
                if ($DB->fieldExists($table, 'groups_id_tech')) {
                    $s_criteria['criteria'][] = [
                        'link' => 'AND',
                        'field' => self::getSearchOptionID($table, 'groups_id_tech', 'glpi_groups'), // group tech
                        'searchtype' => 'equals',
                        'value' => $groups_id
                    ];
                } else if (in_array($table, [
                    Ticket::getTable(),
                    Change::getTable(),
                    Problem::getTable(),
                ])) {
                    $s_criteria['criteria'][] = [
                        'link' => 'AND',
                        'field' => 8, // group tech
                        'searchtype' => 'equals',
                        'value' => $groups_id
                    ];
                }
            }
        }

        if (isset($apply_filters['user_tech'])
            && (int)$apply_filters['user_tech'] > 0) {
            if ($DB->fieldExists($table, 'users_id_tech')) {
                $s_criteria['criteria'][] = [
                    'link' => 'AND',
                    'field' => self::getSearchOptionID($table, 'users_id_tech', 'glpi_users'),// tech
                    'searchtype' => 'equals',
                    'value' => (int)$apply_filters['user_tech']
                ];
            } else if (in_array($table, [
                Ticket::getTable(),
                Change::getTable(),
                Problem::getTable(),
            ])) {
                $s_criteria['criteria'][] = [
                    'link' => 'AND',
                    'field' => 5,// tech
                    'searchtype' => 'equals',
                    'value' => (int)$apply_filters['user_tech']
                ];
            }
        }
        return $s_criteria;
    }


    public static function formatMonthyearDates(string $monthyear): array
    {
        $rawdate = explode('-', $monthyear);
        $year = $rawdate[0];
        $month = $rawdate[1];
        $monthtime = mktime(0, 0, 0, $month, 1, $year);

        $start_day = date("Y-m-d H:i:s", strtotime("first day of this month", $monthtime));
        $end_day = date("Y-m-d H:i:s", strtotime("first day of next month", $monthtime));

        return [$start_day, $end_day];
    }
}