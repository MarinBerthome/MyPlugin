<?php


class PluginMorewidgetsAssistancecards extends CommonDBTM
{
    public static function assistanceCards()
    {
        $cards["sla_evolution"] = [
            'widgettype' => ['stackedbars'],
            'itemtype' => "\\Ticket",
            'group' => __('Assistance'),
            'label' => __('Evolution des SLA '),
            'provider' => "PluginMorewidgetsAssistancecards::getSLAEvolution",
            'filters' => [
                'dates', 'dates_mod', 'itilcategory',
                'group_tech', 'user_tech', 'requesttype', 'location', 'sla'
            ]
        ];

        $cards["old_tickets"] = [
            'widgettype' => ['stackedbars'],
            'itemtype' => "\\Ticket",
            'group' => __('Assistance'),
            'label' => __('Ancienneté des tickets'),
            'provider' => "PluginMorewidgetsAssistancecards::getOldTickets",
            'filters' => [
                'dates', 'dates_mod', 'itilcategory',
                'group_tech', 'user_tech', 'requesttype', 'location','sla'
            ]
        ];

        $cards["ticket_technician"] = [
            'widgettype' => ['stackedbars'],
            'itemtype' => "\\Ticket",
            'group' => __('Assistance'),
            'label' => __('Tickets par techniciens'),
            'provider' => "PluginMorewidgetsAssistancecards::getTicketsTechnician",
            'filters' => [
                'dates', 'dates_mod', 'itilcategory',
                'group_tech', 'user_tech', 'requesttype', 'location','sla'
            ]
        ];

        $cards["sla_evolution_percent"] = [
            'widgettype' => ['stackedbars'],
            'itemtype' => "\\Ticket",
            'group' => __('Assistance'),
            'label' => __('Pourcentage des SLA'),
            'provider' => "PluginMorewidgetsAssistancecards::getSLAEvolutionPercent",
            'filters' => [
                'dates', 'dates_mod', 'itilcategory',
                'group_tech', 'user_tech', 'requesttype', 'location','sla'
            ]
        ];

        $cards["backlogs_evolution"] = [
            'widgettype' => ['stackedbars', 'lines'],
            'itemtype' => "\\Ticket",
            'group' => __('Assistance'),
            'label' => __('Évolution des backlogs sur l\'année passée'),
            'provider' => "PluginMorewidgetsAssistancecards::getBacklogsEvolution",
            'filters' => [
                'dates', 'dates_mod', 'itilcategory',
                'group_tech', 'user_tech', 'requesttype', 'location','sla'
            ]
        ];

        return $cards;

    }

    public static function getSLAEvolution(array $params = []): array
    {
        $DB = DBConnection::getReadConnection();


        $default_params = [
            'label' => "",
            'icon' => Ticket::getIcon(),
            'apply_filters' => [],
        ];
        $params = array_merge($default_params, $params);

        $t_table = Ticket::getTable();

        $sub_query = array_merge_recursive(
            [
                'DISTINCT' => true,
                'SELECT' => ["$t_table.*"],
                'FROM' => $t_table,
                'WHERE' => [
                        "$t_table.is_deleted" => 0,
                        "$t_table.closedate IS NOT NULL",
                    ] + getEntitiesRestrictCriteria($t_table),
            ],
            // limit count for profiles with limited rights
            Ticket::getCriteriaFromProfile(),
            PluginMorewidgetsUtilities::getFiltersCriteria($t_table, $params['apply_filters'])
        );

        $criteria = [
            'SELECT' => [
                new QueryExpression(
                    "FROM_UNIXTIME(UNIX_TIMESTAMP(" . $DB->quoteName("{$t_table}_distinct.date") . "),'%Y-%m') AS period"
                ),
                new QueryExpression(
                    "SUM(IF({$t_table}_distinct.time_to_resolve > {$t_table}_distinct.closedate, 1, 0))
                    as " . $DB->quoteValue(_x('status', 'Respectée'))
                ),
                new QueryExpression(
                    "SUM(IF({$t_table}_distinct.time_to_resolve < {$t_table}_distinct.closedate, 1, 0))  
                    as " . $DB->quoteValue(_x('status', 'Non respectée'))
                ),
            ],
            'FROM' => new QuerySubQuery($sub_query, "{$t_table}_distinct"),
            'ORDER' => 'period ASC',
            'GROUP' => ['period'],

        ];

        $iterator = $DB->request($criteria);

        $s_criteria = [
            'criteria' => [
                [
                    'link' => 'AND',
                    'field' => 12, // status
                    'searchtype' => 'equals',
                    'value' => '6'
                ], [
                    'link' => 'AND',
                    'field' => 15, // creation date
                    'searchtype' => 'morethan',
                    'value' => null
                ], [
                    'link' => 'AND',
                    'field' => 15, // creation date
                    'searchtype' => 'lessthan',
                    'value' => null
                ],
                [
                    'link' => 'AND',
                    'field' => 82,
                    'searchtype' => 'equals',
                    'value' => null
                ],
                PluginMorewidgetsUtilities::getSearchFiltersCriteria($t_table, $params['apply_filters'])
            ],
            'reset' => 'reset'
        ];

        $data = [
            'labels' => [],
            'series' => []
        ];
        foreach ($iterator as $result) {

            list($start_day, $end_day) = PluginMorewidgetsUtilities::formatMonthyearDates($result['period']);
            $s_criteria['criteria'][1]['value'] = $start_day;
            $s_criteria['criteria'][2]['value'] = $end_day;

            $data['labels'][] = $result['period'];
            $tmp = $result;
            unset($tmp['period']);
            $i = 0;

            foreach ($tmp as $label2 => $value) {

                if ($label2 == 'Respectée') {
                    $s_criteria['criteria'][3]['value'] = 0;

                } else {
                    $s_criteria['criteria'][3]['value'] = 1;

                }

                $data['series'][$i]['name'] = $label2;
                $data['series'][$i]['data'][] = [
                    'value' => (int)$value,
                    'url' => Ticket::getSearchURL() . "?" . Toolbox::append_params($s_criteria),
                ];
                $i++;
            }
        }
        return [
            'data' => $data,
            'label' => $params['label'],
            'icon' => $params['icon'],
        ];
    }


    public static function getSLAEvolutionPercent(array $params = []): array
    {

        $DB = DBConnection::getReadConnection();


        $default_params = [
            'label' => "",
            'icon' => Ticket::getIcon(),
            'apply_filters' => [],
        ];
        $params = array_merge($default_params, $params);

        $t_table = Ticket::getTable();

        $sub_query = array_merge_recursive(
            [
                'DISTINCT' => true,
                'SELECT' => ["$t_table.*"],
                'FROM' => $t_table,
                'WHERE' => [
                        "$t_table.is_deleted" => 0,
                        "$t_table.closedate IS NOT NULL",
                    ] + getEntitiesRestrictCriteria($t_table),
            ],
            // limit count for profiles with limited rights
            Ticket::getCriteriaFromProfile(),
            PluginMorewidgetsUtilities::getFiltersCriteria($t_table, $params['apply_filters'])
        );

        $criteria = [
            'SELECT' => [
                new QueryExpression(
                    "FROM_UNIXTIME(UNIX_TIMESTAMP(" . $DB->quoteName("{$t_table}_distinct.date") . "),'%Y-%m') AS period"
                ),
                new QueryExpression(
                    "SUM(IF({$t_table}_distinct.time_to_resolve > IFNULL({$t_table}_distinct.closedate, 0), 1, 0)) / COUNT({$t_table}_distinct.time_to_resolve) * 100 
                    as " . $DB->quoteValue(_x('status', 'Respectée'))
                ),
                new QueryExpression(
                    "SUM(IF({$t_table}_distinct.time_to_resolve < IFNULL({$t_table}_distinct.closedate, 0), 1, 0)) / COUNT({$t_table}_distinct.time_to_resolve) * 100 
                    as " . $DB->quoteValue(_x('status', 'Non respectée'))
                ),
            ],
            'FROM' => new QuerySubQuery($sub_query, "{$t_table}_distinct"),
            'ORDER' => 'period ASC',
            'GROUP' => ['period']
        ];
        $iterator = $DB->request($criteria);

        $s_criteria = [
            'criteria' => [
                [
                    'link' => 'AND',
                    'field' => 12, // status
                    'searchtype' => 'equals',
                    'value' => '6'
                ], [
                    'link' => 'AND',
                    'field' => 15, // creation date
                    'searchtype' => 'morethan',
                    'value' => null
                ], [
                    'link' => 'AND',
                    'field' => 15, // creation date
                    'searchtype' => 'lessthan',
                    'value' => null
                ],
                [
                    'link' => 'AND',
                    'field' => 82,
                    'searchtype' => 'equals',
                    'value' => null
                ],
                PluginMorewidgetsUtilities::getSearchFiltersCriteria($t_table, $params['apply_filters'])
            ],
            'reset' => 'reset'
        ];

        $data = [
            'labels' => [],
            'series' => []
        ];
        foreach ($iterator as $result) {

            list($start_day, $end_day) = PluginMorewidgetsUtilities::formatMonthyearDates($result['period']);
            $s_criteria['criteria'][1]['value'] = $start_day;
            $s_criteria['criteria'][2]['value'] = $end_day;

            $data['labels'][] = $result['period'];
            $tmp = $result;
            unset($tmp['period']);
            $i = 0;

            foreach ($tmp as $label2 => $value) {

                if ($label2 == 'Respectée') {
                    $s_criteria['criteria'][3]['value'] = 0;

                } else {
                    $s_criteria['criteria'][3]['value'] = 1;

                }

                $data['series'][$i]['name'] = $label2;
                $data['series'][$i]['data'][] = [
                    'value' => (int)$value,
                    'url' => Ticket::getSearchURL() . "?" . Toolbox::append_params($s_criteria),
                ];
                $i++;
            }
        }
        return [
            'data' => $data,
            'label' => $params['label'],
            'icon' => $params['icon'],
        ];
    }

    public static function getOldTickets(array $params = []): array
    {
        $DB = DBConnection::getReadConnection();


        $default_params = [
            'label' => "",
            'icon' => Ticket::getIcon(),
            'apply_filters' => [],
        ];
        $params = array_merge($default_params, $params);

        $t_table = Ticket::getTable();

        $sub_query = array_merge_recursive(
            [
                'DISTINCT' => true,
                'SELECT' => ["$t_table.*"],
                'FROM' => $t_table,
                'WHERE' => [
                        "$t_table.is_deleted" => 0,
                    ] + getEntitiesRestrictCriteria($t_table),
            ],
            // limit count for profiles with limited rights
            Ticket::getCriteriaFromProfile(),
            PluginMorewidgetsUtilities::getFiltersCriteria($t_table, $params['apply_filters'])
        );

        $criteria = [
            'SELECT' => [
                new QueryExpression(
                    "FROM_UNIXTIME(UNIX_TIMESTAMP(" . $DB->quoteName("{$t_table}_distinct.date") . "),'%Y-%m') AS period"
                ),
                new QueryExpression(
                    "SUM(case {$t_table}_distinct.status when '1' then 1 else 0 end)
                    as " . $DB->quoteValue(_x('status', 'Nouveau'))
                ),
                new QueryExpression(
                    "SUM(case {$t_table}_distinct.status when '2' then 1 else 0 end)
                    as " . $DB->quoteValue(_x('status', 'En cours (Attribué)'))
                ),
                new QueryExpression(
                    "SUM(case {$t_table}_distinct.status when '3' then 1 else 0 end)
                    as " . $DB->quoteValue(_x('status', 'En cours (Planifié)'))
                ),
                new QueryExpression(
                    "SUM(case {$t_table}_distinct.status when '4' then 1 else 0 end)
                    as " . $DB->quoteValue(_x('status', 'En attente'))
                ),
            ],
            'FROM' => new QuerySubQuery($sub_query, "{$t_table}_distinct"),
            'ORDER' => 'period ASC',
            'GROUP' => ['period'],

        ];
        $iterator = $DB->request($criteria);

        $s_criteria = [
            'criteria' => [
                [
                    'link' => 'AND',
                    'field' => 12, // status
                    'searchtype' => 'equals',
                    'value' => null
                ], [
                    'link' => 'AND',
                    'field' => 15, // creation date
                    'searchtype' => 'morethan',
                    'value' => null
                ], [
                    'link' => 'AND',
                    'field' => 15, // creation date
                    'searchtype' => 'lessthan',
                    'value' => null
                ],
                PluginMorewidgetsUtilities::getSearchFiltersCriteria($t_table, $params['apply_filters'])
            ],
            'reset' => 'reset'
        ];

        $data = [
            'labels' => [],
            'series' => []
        ];

        foreach ($iterator as $result) {


            list($start_day, $end_day) = PluginMorewidgetsUtilities::formatMonthyearDates($result['period']);
            $s_criteria['criteria'][1]['value'] = $start_day;
            $s_criteria['criteria'][2]['value'] = $end_day;

            $data['labels'][] = $result['period'];
            unset($result['period']);

            $i = 0;

            foreach ($result as $label2 => $value) {

                switch ($label2) {
                    case 'Nouveau':
                    {
                        $s_criteria['criteria'][0]['value'] = 1;
                        break;
                    }
                    case 'En cours (Attribué)':
                    {
                        $s_criteria['criteria'][0]['value'] = 2;
                        break;
                    }
                    case 'En cours (Planifié)':
                    {
                        $s_criteria['criteria'][0]['value'] = 3;
                        break;
                    }
                    case 'En attente':
                    {
                        $s_criteria['criteria'][0]['value'] = 4;
                        break;
                    }
                }
                $data['series'][$i]['name'] = $label2;
                $data['series'][$i]['data'][] = [
                    'value' => (int)$value,
                    'url' => Ticket::getSearchURL() . "?" . Toolbox::append_params($s_criteria),
                ];
                $i++;
            }
        }
        return [
            'data' => $data,
            'label' => $params['label'],
            'icon' => $params['icon'],
        ];
    }


    public static function getBacklogsEvolution(array $params = []): array
    {
        $DB = DBConnection::getReadConnection();

        $default_params = [
            'label' => "",
            'icon' => Ticket::getIcon(),
            'apply_filters' => [],
        ];
        $params = array_merge($default_params, $params);

        $year = date("Y") - 15;
        $begin = date("Y-m-d", mktime(1, 0, 0, (int)date("m"), (int)date("d"), $year));
        $end = date("Y-m-d");

        if (isset($params['apply_filters']['dates'])
            && count($params['apply_filters']['dates']) == 2) {
            $begin = date("Y-m-d", strtotime($params['apply_filters']['dates'][0]));
            $end = date("Y-m-d", strtotime($params['apply_filters']['dates'][1]));
            unset($params['apply_filters']['dates']);
        }

        $t_table = Ticket::getTable();

        $series = [

            'inter_total' => [
                'name' => _nx('ticket', 'Opened', 'Opened', \Session::getPluralNumber()),
                'search' => [
                    'criteria' => [
                        [
                            'link' => 'AND',
                            'field' => 15, // creation date
                            'searchtype' => 'morethan',
                            'value' => null
                        ], [
                            'link' => 'AND',
                            'field' => 15, // creation date
                            'searchtype' => 'lessthan',
                            'value' => null
                        ], PluginMorewidgetsUtilities::getSearchFiltersCriteria($t_table, $params['apply_filters']),
                    ],
                    'reset' => 'reset'
                ]
            ],
            'inter_solved' => [
                'name' => _nx('ticket', 'Solved', 'Solved', \Session::getPluralNumber()),
                'search' => [
                    'criteria' => [
                        [
                            'link' => 'AND',
                            'field' => 17, // solve date
                            'searchtype' => 'morethan',
                            'value' => null
                        ], [
                            'link' => 'AND',
                            'field' => 17, // solve date
                            'searchtype' => 'lessthan',
                            'value' => null
                        ], PluginMorewidgetsUtilities::getSearchFiltersCriteria($t_table, $params['apply_filters']),
                    ],
                    'reset' => 'reset'
                ]
            ],
            'inter_closed' => [
                'name' => __('Closed'),
                'search' => [
                    'criteria' => [
                        [
                            'link' => 'AND',
                            'field' => 16, // close date
                            'searchtype' => 'morethan',
                            'value' => null
                        ], [
                            'link' => 'AND',
                            'field' => 16, // close date
                            'searchtype' => 'lessthan',
                            'value' => null
                        ], PluginMorewidgetsUtilities::getSearchFiltersCriteria($t_table, $params['apply_filters']),
                    ],
                    'reset' => 'reset'
                ]
            ],
        ];

        $filters = array_merge_recursive(
            Ticket::getCriteriaFromProfile(),
            PluginMorewidgetsUtilities::getFiltersCriteria($t_table, $params['apply_filters'])
        );

        $total = array();

        $i = 0;
        $monthsyears = [];
        foreach ($series as $stat_type => &$serie) {
            $values = Stat::constructEntryValues(
                'Ticket',
                $stat_type,
                $begin,
                $end,
                "",
                "",
                "",
                $filters
            );

            if ($i === 0) {
                $monthsyears = array_keys($values);
            }
            $values = array_values($values);
            foreach ($values as $index => $number) {
                $current_monthyear = $monthsyears[$index];
                list($start_day, $end_day) = PluginMorewidgetsUtilities::formatMonthyearDates($current_monthyear);
                $serie['search']['criteria'][0]['value'] = $start_day;
                $serie['search']['criteria'][1]['value'] = $end_day;

                $serie['data'][$index] = [
                    'value' => $number,
                    'url' => Ticket::getSearchURL() . "?" . Toolbox::append_params($serie['search']),
                ];
                $total[$i][$index] = [
                    'value' => $number,
                    'date' => $end_day,
                ];
            }
            $i++;
        }

        foreach ($total as $first => $val) {

            if ($first == 0) {
                foreach ($val as $point => $value) {
                    $result =
                        ($value['value'] + $total[$first][$point - 1]['value']) - $total[2][$point]['value'];

                    $total[$first][$point]['value'] = $result;

                }
            }

        }

        $s_criteria = [
            'criteria' => [
                [
                    'link' => 'AND',
                    'field' => 12, // status
                    'searchtype' => 'equals',
                    'value' => 'notclosed'
                ],
                [
                    'link' => 'AND',
                    'field' => 15, // creation date
                    'searchtype' => 'lessthan',
                    'value' => null
                ],
                PluginMorewidgetsUtilities::getSearchFiltersCriteria($t_table, $params['apply_filters'])
            ],
            'reset' => 'reset'
        ];

        foreach ($total as $type => $val) {
            if ($type == 0) {
                foreach ($val as $value) {
                    PluginMorewidgetsUtilities::formatMonthyearDates($type);
                    $s_criteria['criteria'][1]['value'] = $value['date'];
                    $series[3]['name'] = 'Backlogs';
                    $series[3]['data'][] = [
                        'value' => (int)$value['value'],
                        'url' => Ticket::getSearchURL() . '?' . Toolbox::append_params($s_criteria),
                    ];

                }
            }
        }


        return [
            'data' => [
                'labels' => $monthsyears,
                'series' => array_values($series),
            ],
            'label' => $params['label'],
            'icon' => $params['icon'],
        ];
    }

    public static function getTicketsTechnician(array $params = []): array
    {

        $DB = DBConnection::getReadConnection();

        $default_params = [
            'label' => "",
            'icon' => Ticket::getIcon(),
            'apply_filters' => [],
        ];
        $params = array_merge($default_params, $params);


        $t_table = Ticket::getTable();
        $ug_table = User::getTable();


        $where = [
            "$t_table.is_deleted" => 0,
        ];

        $criteria = array_merge_recursive(
            [
                'SELECT' => array_merge([
                    'COUNT' => "$t_table.closedate AS nb_tickets",
                    "$ug_table.id as actor_id",
                    "$ug_table.name as name"
                ]),
                'FROM' => $ug_table,
                'LEFT JOIN' => [
                    $t_table => [
                        'ON' => [
                            $ug_table => 'id',
                            $t_table => 'users_id_lastupdater',
                        ]
                    ]
                ],
                'GROUPBY' => "$ug_table.id",
            ],
            Ticket::getCriteriaFromProfile(),
            PluginMorewidgetsUtilities::getFiltersCriteria($t_table, $params['apply_filters'])
        );

        $iterator = $DB->request($criteria);

        $s_criteria = [
            'criteria' => [
                [
                    'link' => 'AND',
                    'field' => 12,
                    'searchtype' => 'equals',
                    'value' => 6
                ], [
                    'link' => 'AND',
                    'field' => 5,
                    'searchtype' => 'equals',
                    'value' => null,
                ],
                PluginMorewidgetsUtilities::getSearchFiltersCriteria($t_table, $params['apply_filters']),
            ],
            'reset' => 'reset'
        ];

        $names = [];
        $data = [];

        foreach ($iterator as $result) {

            if($result['nb_tickets'] > 0) {

                $s_criteria['criteria'][1]['value'] = $result['actor_id'];
                $names[] = $result['name'];
                $data['number']['name'] = 'Tickets clos';
                $data['number']['data'][] = [
                    'value' => $result['nb_tickets'],
                    'url' => Ticket::getSearchURL() . "?" . Toolbox::append_params($s_criteria),
                ];
            }

        }


        return [
            'data' => [
                'labels' => $names,
                'series' => array_values($data),
            ],
            'label' => $params['label'],
            'icon' => $params['icon'],
        ];
    }
}