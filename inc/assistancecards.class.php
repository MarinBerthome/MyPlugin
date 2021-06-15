<?php


class PluginMorewidgetsAssistancecards extends CommonDBTM
{
    /**
     * Retourne un tableau de nouvelles cartes pour le tableau de bord assistance
     * @return array
     */
    public static function assistanceCards(): array
    {
        $cards["sla_evolution"] = [
            'widgettype' => ['stackedbars'],
            'itemtype' => "\\Ticket",
            'group' => __('Assistance'),
            'label' => __('Evolution des SLA '),
            'provider' => "PluginMorewidgetsAssistancecards::getSLAEvolution",
            'args'     => [
                'case' => 'normal',
            ],
            'filters' => [
                'dates', 'dates_mod', 'itilcategory',
                'group_tech', 'user_tech', 'requesttype', 'location', 'sla'
            ]
        ];

        $cards["sla_evolution_percent"] = [
            'widgettype' => ['stackedbars'],
            'itemtype' => "\\Ticket",
            'group' => __('Assistance'),
            'label' => __('Pourcentage des SLA'),
            'provider' => "PluginMorewidgetsAssistancecards::getSLAEvolution",
            'args'     => [
                'case' => 'percent',
            ],
            'filters' => [
                'dates', 'dates_mod', 'itilcategory',
                'group_tech', 'user_tech', 'requesttype', 'location','sla'
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

        return $cards;

    }

    /**
     * Retourne l'evolution des SLA
     * En fonction de l'argument on retourne un pourcentage ou le nombre de tickets
     * @param string $case :
     * - 'percent' : retourner le pourcentage de SLA
     * - 'normal' :  avoir la quantité de SLA
     *
     * @param array $params default values for
     * - 'title' of the card
     * - 'icon' of the card
     * - 'apply_filters' values from dashboard filters
     * @return array
     */
    public static function getSLAEvolution(
        string $case = "",
        array $params = []): array
    {
        $DB = DBConnection::getReadConnection();

        $default_params = [
            'label' => "",
            'icon' => Ticket::getIcon(),
            'apply_filters' => [],
        ];
        $params = array_merge($default_params, $params);

        $t_table = Ticket::getTable();

        /**
         * On recupère tous les tickets de la table ticket qui ne sont pas supprimés et qui sont uniquement fermés
         *
         */
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

        $criteria = array();

        /**
         * En fonction des données désirée on va faire une requête SQL différente
         */
        switch ($case)
        {
            case 'percent' :
            {
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
            }
            case 'normal' :
            {
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
            }
        }

        $iterator = $DB->request($criteria);

        /**
         * URL pour retrouver les tickets concernés quand on clique dessus
         */
        $s_criteria = [
            'criteria' => [
                [
                    'link' => 'AND',
                    'field' => 12, // status
                    'searchtype' => 'equals',
                    'value' => '6' // clos
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
                    'field' => 82, // TTR dépassé
                    'searchtype' => 'equals',
                    'value' => null
                ],
                PluginMorewidgetsUtilities::getSearchFiltersCriteria($t_table, $params['apply_filters'])
            ],
            'reset' => 'reset'
        ];

        /**
         * $data est le tableau qui est retourné par la fonction
         * - labels[] : c'est le texte qui correspondera à chacune des barres du graphique, ici ce sera des dates
         * - series[] : correspond au données associées au labels on retrouvera :
         *              - series[0]['label'] : nom associée à la donnée (ici ce sera soit respectée soit pas respectée)
         *              - series[0]['data']['value'] : valeur associée
         *              - series[0]['data']['url'] : url associé qui est derterminé avec le tableau $s_criteria
         */
        $data = [
            'labels' => [],
            'series' => []
        ];

        /**
         * Pour chaque colonne qui resulte de la requête SQL
         */
        foreach ($iterator as $result) {

            /**
             * On récupère la date
             */
            list($start_day, $end_day) = PluginMorewidgetsUtilities::formatMonthyearDates($result['period']);
            $s_criteria['criteria'][1]['value'] = $start_day;
            $s_criteria['criteria'][2]['value'] = $end_day;

            /**
             * On l'assigne au tableau $data
             *
             */
            $data['labels'][] = $result['period'];
            $tmp = $result;

            unset($tmp['period']);
            $i = 0;

            foreach ($tmp as $label2 => $value) {

                /**
                 * Pour chaque valeur on regarde à quelle catégorie elle correspond.
                 * En fonction de celle-ci, on met 0 ou 1 dans l'Url ($s_criteria)
                 * - 0 = SLA Respecté
                 * - 1 = SLA Non respecté
                 */
                if ($label2 == 'Respectée') {
                    $s_criteria['criteria'][3]['value'] = 0;

                } else {
                    $s_criteria['criteria'][3]['value'] = 1;

                }

                /**
                 * On assigne la valeur dans le tableau $data avec le nom et l'url associé
                 */
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

    /**
     * Retourne tous les tickets tickets oubliés par mois et par status
     * @param array $params default values for
     * - 'title' of the card
     * - 'icon' of the card
     * - 'apply_filters' values from dashboard filters
     *
     * @return array
     */
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

    /**
     * Retourne l'évolution du backlogs accompagné de l'évolution des tickets clos, résolus et ouverts
     *
     * @param array $params default values for
     * - 'title' of the card
     * - 'icon' of the card
     * - 'apply_filters' values from dashboard filters
     *
     * @return array
     */
    public static function getBacklogsEvolution(array $params = []): array
    {
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
                'name' => _nx('ticket', 'Opened', 'Opened', Session::getPluralNumber()),
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
                'name' => _nx('ticket', 'Solved', 'Solved', Session::getPluralNumber()),
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

        $monthsYears = [];
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
                $monthsYears = array_keys($values);
            }

            $values = array_values($values);

            foreach ($values as $index => $number) {
                $currentMonthYear = $monthsYears[$index];
                list($start_day, $end_day) = PluginMorewidgetsUtilities::formatMonthyearDates($currentMonthYear);
                $serie['search']['criteria'][0]['value'] = $start_day;
                $serie['search']['criteria'][1]['value'] = $end_day;

                $serie['data'][$index] = [
                    'value' => $number,
                    'url' => Ticket::getSearchURL() . "?" . Toolbox::append_params($serie['search']),
                ];

                /**
                 * Ce tableau va permettre de calculer le backlog
                 */
                $total[$i][$index] = [
                    'value' => $number,
                    'date' => $end_day,
                ];
            }
            $i++;
        }


        /**
         * Calcul du backlog en additionant le nombre de tickets ouverts sur le mois + les tickets
         * ouverts restant le mois précedant. Le tout soustrait par le nombre de tickets résolu sur le ticket
         * Il est possible de faire ça en parcourant le tableau $total
         */
        foreach ($total as $first => $val) if ($first == 0) foreach ($val as $point => $value) {

            $result = ($value['value'] + $total[$first][$point - 1]['value']) - $total[2][$point]['value'];
            $total[$first][$point]['value'] = $result;
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
            break;
        }


        return [
            'data' => [
                'labels' => $monthsYears,
                'series' => array_values($series),
            ],
            'label' => $params['label'],
            'icon' => $params['icon'],
        ];
    }

    /**
     * Retourne le nombre total de tickets résolus par techniciens
     * @param array $params default values for
     * - 'title' of the card
     * - 'icon' of the card
     * - 'apply_filters' values from dashboard filters
     * @return array
     */
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

        /**
         * La requête permet de récuperer le nombre de tickets clos par techniciens
         * et leurs noms associés.
         */
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
                'WHERE' => $where,
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

            /**
             * Si il y a au moins un ticket de résolu on recupère les données
             */
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