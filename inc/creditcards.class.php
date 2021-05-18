<?php

class CreditCards extends CommonDBTM
{
    public static function dashboardCards()
    {
        $cards = array();

        $cards["bn_count_credit"] = [
            'widgettype' => ["bigNumber"],
            'itemtype'   => "\\Credit",
            'group'      => __('Credit'),
            'label'      => __("Nombre de crédits"),
            'provider'   => "CreditCards::nbCredits",
            'filters'    => [
                'credit'
            ]
        ];

        $cards["bn_credit_remaining"] = [
            'widgettype' => ["bigNumber"],
            'itemtype'   => "\\Credit",
            'group'      => __('Credit'),
            'label'      => __("Nombre de crédits restant"),
            'provider'   => "CreditCards::nbCreditsRemaining",
            'filters'    => [
                'credit'
            ]
        ];

        $cards["bn_percent_credit_remaining"] = [
            'widgettype' => ["bigNumber"],
            'itemtype'   => "\\Credit",
            'group'      => __('Credit'),
            'label'      => __("Pourcentage de crédits restant"),
            'provider'   => "CreditCards::percentCreditsRemaining",
            'filters'    => [
                'credit'
            ]
        ];

        $cards["bn_percent_credit_used"] = [
            'widgettype' => ["bigNumber"],
            'itemtype'   => "\\Credit",
            'group'      => __('Credit'),
            'label'      => __("Pourcentage de crédits utilisé"),
            'provider'   => "CreditCards::percentCreditsUsed",
            'filters'    => [
                'credit'
            ]
        ];

        $cards["credit_used"] = [
            'widgettype' => ["bigNumber"],
            'itemtype'   => "\\Credit",
            'group'      => __('Credit'),
            'label'      => __("Credit utilisé"),
            'provider'   => "CreditCards::nbCreditsUsed",
            'filters'    => [
                'credit'
            ]
        ];

        // add specific ticket's cases
        $cards["nb_opened_ticket"] = [
            'widgettype' => ['line', 'area', 'bar'],
            'itemtype'   => "\\Ticket",
            'group'      => __('Assistance'),
            'label'      => __("Number of tickets by month"),
            'provider'   => "CreditCards::ticketsOpened",
            'filters'    => [
                'dates', 'dates_mod', 'itilcategory',
                'group_tech', 'user_tech', 'requesttype', 'location'
            ]
        ];

        $cards["date_end_ticket"] = [
            'widgettype' => ["summaryNumbers"],
            'itemtype'   => "\\Credit",
            'group'      => __('Credit'),
            'label'      => __("Date de fin du contrat"),
            'provider'   => "CreditCards::getDateEndingCredit",
            'filters'    => [
                'credit'
            ]
        ];


        $cards["credits_consumption_ticket"] = [
            'widgettype' => ['bars', 'stackedbars', 'lines'],
            'itemtype'   => "\\Credit",
            'group'      => __('Credit'),
            'label'      => __("Consommation de crédit par ticket"),
            'provider'   => "CreditCards::getCreditsConsumption",
            'filters'    => [
                'credit'
            ]
        ];


        $cards["credits_evolution_period"] = [
            'widgettype' => ['lines', 'bars', 'areas'],
            'itemtype'   => "\\Credit",
            'group'      => __('Credit'),
            'label'      => __("Evolution de la consommation de crédit"),
            'provider'   => "CreditCards::getCreditsEvolution",
            'filters'    => [
                'credit'
            ]
        ];

        return $cards;
    }

    public static function nbCredits(array $params = []): array {
        $DB = DBConnection::getReadConnection();
        $default_params = [
            'label'         => "",
            'icon'          => \PluginCreditEntity::getIcon(),
            'apply_filters' => [],
        ];
        $params = array_merge($default_params, $params);



        $t_table = \PluginCreditEntity::getTable();
        $criteria = array_merge_recursive(
            [
                'SELECT' => [
                    'quantity',
                    'name'
                ],
                'FROM'    => $t_table,

            ],
            self::getFiltersCriteria($t_table, $params['apply_filters'])
        );

        $iterator = $DB->request($criteria);
        $result = $iterator->next();
        $nb_items = $result['quantity'];
        $nom = $result['name'];

        return [
            'number'      => $nb_items,
            'url'         => '',
            'label'       =>  $nom,
            'icon'        => $default_params['icon'],
        ];
    }

    /**
     * get the ending date of the credit subscription
     *
     * @param array $params default values for
     * - 'title' of the card
     * - 'icon' of the card
     * - 'apply_filters' values from dashboard filters
     *
     * @return array
     */
    public static function getDateEndingCredit(array $params = []): array {
        $DB = DBConnection::getReadConnection();
        $default_params = [
            'label'         => "",
            'icon'          => \PluginCreditEntity::getIcon(),
            'apply_filters' => [],
        ];
        $params = array_merge($default_params, $params);

        $t_table = \PluginCreditEntity::getTable();
        $criteria = array_merge_recursive(
            [
                'SELECT' => [
                    'end_date',
                ],
                'FROM'    => $t_table,

            ],
            self::getFiltersCriteria($t_table, $params['apply_filters'])
        );

        $iterator = $DB->request($criteria);
        $result = $iterator->next();
        $date =  date_create($result['end_date']);
        $date = date_format($date, 'm/y');

        return [
            'number'      => $date,
            'url'         => '',
            'label'       =>  'Fin du contrat',
            'icon'        => $default_params['icon'],
        ];
    }

    /**
     * get the quantity off the credit used
     *
     * @param array $params default values for
     * - 'title' of the card
     * - 'icon' of the card
     * - 'apply_filters' values from dashboard filters
     *
     * @return array
     */
    public static function nbCreditsUsed(array $params = []): array {
        $DB = DBConnection::getReadConnection();
        $default_params = [
            'label'         => "",
            'icon'          => \PluginCreditEntity::getIcon(),
            'apply_filters' => [],
        ];
        $params = array_merge($default_params, $params);

        $t_table = \PluginCreditEntity::getTable();
        $s_table = \PluginCreditTicket::getTable();
        $criteria = array_merge_recursive(
            [
                'SELECT' => [
                    "$t_table.quantity",
                    'SUM' => "$s_table.consumed AS sum",
                ],
                'FROM'    => $t_table,
                'INNER JOIN' => [
                    $s_table => [
                        'ON' => [
                            $s_table  => 'plugin_credit_entities_id',
                            $t_table => 'id',
                        ],
                    ],
                ],
            ],
            self::getFiltersCriteria($t_table, $params['apply_filters'])
        );
        $iterator = $DB->request($criteria);
        $result = $iterator->next();


        $nb_items =  $result['sum'];//$result['result'];
        $nom = 'Credit utilisé ';//$result['name'];

        return [
            'number'      => $nb_items,
            'url'         => '',
            'label'       =>  $nom,
            'icon'        => $default_params['icon'],
        ];
    }

    /**
     * get the quantity off the credit remaining
     *
     * @param array $params default values for
     * - 'title' of the card
     * - 'icon' of the card
     * - 'apply_filters' values from dashboard filters
     *
     * @return array
     */
    public static function nbCreditsRemaining(array $params = []): array {
        $DB = DBConnection::getReadConnection();
        $default_params = [
            'label'         => "",
            'icon'          => \PluginCreditEntity::getIcon(),
            'apply_filters' => [],
        ];
        $params = array_merge($default_params, $params);

        $t_table = \PluginCreditEntity::getTable();
        $s_table = \PluginCreditTicket::getTable();
        $criteria = array_merge_recursive(
            [
                'SELECT' => [
                    "$t_table.quantity",
                    'SUM' => "$s_table.consumed AS sum",
                ],
                'FROM'    => $t_table,
                'INNER JOIN' => [
                    $s_table => [
                        'ON' => [
                            $s_table  => 'plugin_credit_entities_id',
                            $t_table => 'id',
                        ],
                    ],
                ],
            ],
            self::getFiltersCriteria($t_table, $params['apply_filters'])
        );
        $iterator = $DB->request($criteria);
        $result = $iterator->next();
        $result =  $result['quantity'] - $result['sum'];
        $nom = 'Credit restant ';//$result['name'];

        return [
            'number'      => $result,
            'url'         => '',
            'label'       =>  $nom,
            'icon'        => $default_params['icon'],
        ];
    }

    public static function percentCreditsUsed(array $params = []): array {
        $DB = DBConnection::getReadConnection();
        $default_params = [
            'label'         => "",
            'icon'          => \PluginCreditEntity::getIcon(),
            'apply_filters' => [],
        ];
        $params = array_merge($default_params, $params);

        $t_table = \PluginCreditEntity::getTable();
        $s_table = \PluginCreditTicket::getTable();
        $criteria = array_merge_recursive(
            [
                'SELECT' => [
                    "$t_table.quantity",
                    'SUM' => "$s_table.consumed AS sum",
                ],
                'FROM'    => $t_table,
                'INNER JOIN' => [
                    $s_table => [
                        'ON' => [
                            $s_table  => 'plugin_credit_entities_id',
                            $t_table => 'id',
                        ],
                    ],
                ],
            ],
            self::getFiltersCriteria($t_table, $params['apply_filters'])
        );
        $iterator = $DB->request($criteria);
        $result = $iterator->next();
        $resulting =  ($result['sum']) / $result['quantity'] * 100;
        $resulting = $resulting.'%';

        $nb_items =  $resulting;//$result['result'];
        $nom = 'Credit utilisé ';//$result['name'];

        return [
            'number'      => $nb_items,
            'url'         => '',
            'label'       =>  $nom,
            'icon'        => $default_params['icon'],
        ];
    }

    public static function percentCreditsRemaining(array $params = []): array {
        $DB = DBConnection::getReadConnection();
        $default_params = [
            'label'         => "",
            'icon'          => \PluginCreditEntity::getIcon(),
            'apply_filters' => [],
        ];
        $params = array_merge($default_params, $params);

        $t_table = \PluginCreditEntity::getTable();
        $s_table = \PluginCreditTicket::getTable();
        $criteria = array_merge_recursive(
            [
                'SELECT' => [
                    "$t_table.quantity",
                    'SUM' => "$s_table.consumed AS sum",
                ],
                'FROM'    => $t_table,
                'INNER JOIN' => [
                    $s_table => [
                        'ON' => [
                            $s_table  => 'plugin_credit_entities_id',
                            $t_table => 'id',
                        ],
                    ],
                ],
            ],
            self::getFiltersCriteria($t_table, $params['apply_filters'])
        );
        $iterator = $DB->request($criteria);
        $result = $iterator->next();
        $resulting =  (($result['quantity'] - $result['sum']) / $result['quantity']) * 100;
        $resulting = $resulting.'%';

        $nb_items =  $resulting;//$result['result'];
        $nom = 'Credit restant ';//$result['name'];

        return [
            'number'      => $nb_items,
            'url'         => '',
            'label'       =>  $nom,
            'icon'        => $default_params['icon'],
        ];
    }

    /**
     * Get ticket evolution by opened, solved, closed, late series and months group
     *
     * @param array $params default values for
     * - 'title' of the card
     * - 'icon' of the card
     * - 'apply_filters' values from dashboard filters
     *
     * @return array
     */
    public static function getCreditsConsumption(array $params = []): array {
        $DB = DBConnection::getReadConnection();
        $default_params = [
            'label'         => "",
            'icon'          => "",
            'apply_filters' => [],
        ];
        $params = array_merge($default_params, $params);

        $t_table = \PluginCreditEntity::getTable();
        $s_table = \PluginCreditTicket::getTable();
        //Get the start date and the end date

        $criteria = array_merge_recursive(
            [
                'SELECT' => [
                    "$t_table.begin_date",
                    "$t_table.end_date",
                    "$s_table.consumed",
                    "$s_table.date_creation",
                    "$s_table.tickets_id"
                ],
                'FROM'    => $t_table,
                'INNER JOIN' => [
                    $s_table => [
                        'ON' => [
                            $s_table  => 'plugin_credit_entities_id',
                            $t_table => 'id',
                        ],
                    ],
                ],
            ],
            self::getFiltersCriteria($t_table, $params['apply_filters'])
        );

        $iterator = $DB->request($criteria);
        $result = $iterator->next();

        $filters = array_merge_recursive(
            self::getFiltersCriteria($t_table, $params['apply_filters'])
        );

        $begin  = $result['begin_date'];
        $end    = $result['end_date'];
        $series = [];
        $monthsyears = [];
        $iterator = $DB->request($criteria);
        $i = 0;

        while ($row = $iterator->next()) {

            $monthsyears[$i] = $row['date_creation'];
            $date = $row['date_creation'];
            //$visites = round($row['total_visites']);
            $series['credit']['name'] = 'Crédit';
            $series['credit']['data'][$i] = [
                'value' => $row['consumed'],
                'url' =>  Ticket::getFormURL()."?id=".$row['tickets_id']
            ];
            $i++;
        }


        return [
            'data'  => [
                'labels' => $monthsyears,
                'series' => array_values($series),
            ],
            'label' => $params['label'],
            'icon'  => $params['icon'],
        ];
    }

    /**
     * Get ticket evolution by opened, solved, closed, late series and months group
     *
     * @param array $params default values for
     * - 'title' of the card
     * - 'icon' of the card
     * - 'apply_filters' values from dashboard filters
     *
     * @return array
     */
    public static function getCreditsEvolution(array $params = []): array {
        $DB = DBConnection::getReadConnection();
        $default_params = [
            'label'         => "",
            'icon'          => "",
            'apply_filters' => [],
        ];


        $params = array_merge($default_params, $params);

        $t_table = \PluginCreditEntity::getTable();
        $s_table = \PluginCreditTicket::getTable();
        //Get the start date and the end date

        $criteria = array_merge_recursive(
            [
                'SELECT' => [
                    "$t_table.begin_date",
                    "$t_table.end_date",
                    "$s_table.consumed",
                    "$s_table.date_creation"
                ],
                'FROM'    => $t_table,
                'INNER JOIN' => [
                    $s_table => [
                        'ON' => [
                            $s_table  => 'plugin_credit_entities_id',
                            $t_table => 'id',
                        ],
                    ],
                ],

            ],
            self::getFiltersCriteria($t_table, $params['apply_filters'])
        );

        $iterator = $DB->request($criteria);
        $result = $iterator->next();

        $filters = array_merge_recursive(
            self::getFiltersCriteria($t_table, $params['apply_filters'])
        );
        $i = 0;
        $monthsYears = [];
        $begin =  date_create($result['begin_date']);
        $end   = date_create($result['end_date']);


        $interval = \DateInterval::createFromDateString('1 month');
        $period = new \DatePeriod($begin, $interval, $end);

        foreach ($period as $dt) {
            $monthsYears[$i] = $dt->format('m/y');
            $i++;
        }


        $series = [];


        $series = [];
        $value = 0;
        $v = 0;

        $values = [];
        foreach($monthsYears as $month)
        {
            $iterator = $DB->request($criteria);
            $z = 0;
            while ($row = $iterator->next()) {

                $date = date_create($row['date_creation']);
                $date = date_format($date, 'm/y');

                if($month == $date) {

                    $values[$month][$z] = $row['consumed'];
                    $z++;
                }
                else
                {
                    $values[$month][$z] = 0;
                    $z++;
                }
            }
        }

        $total = array();
        $z = 0;
        foreach($values as $clef => $value){

            $sum = 0;
            foreach($value as $c => $v)
            {
                $sum += $v;
                unset($v);

            }
            $total['parmois']['name'] = 'Crédit utilisé par mois';
            $total['parmois']['data'][$z] = $sum;
            $z++;
        }

        $z = 0;

        $sum = 0;
        foreach($values as $clef => $value){
            foreach($value as $c => $v)
            {
                $sum += $v;
                unset($v);

            }
            $total['evolution']['name'] = 'Total accumulé';
            $total['evolution']['data'][$z] = $sum;
            $z++;
        }

        return [
            'data'  => [
                'labels' => $monthsYears,
                'series' => array_values($total),
            ],
            'label' => $params['label'],
            'icon'  => $params['icon'],
        ];
    }
}