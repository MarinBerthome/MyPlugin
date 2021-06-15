<?php

class PluginMorewidgetsCreditcards extends CommonDBTM
{
    /**
     * Retourne un tableau de cartes manipulant les données crédits
     * @return array
     */
    public static function creditCards(): array
    {
        $cards = array();

        //Carte qui compte le nombre de crédit inital
        $cards["bn_count_credit"] = [

            //Le type de widget ici bigNumber car on va juste afficher un nombre, c'est la carte de base GLPI
            'widgettype' => ["bigNumber"],

            'itemtype' => "\\Credit",

            //Elle appartiens au groupe Credit, ce groupe s'affiche lorsque l'ont veut ajouter une nouvelle carte
            'group' => __('Credit'),

            //Texte qui apparait dans la liste des cartes disponibles
            'label' => __("Crédit initial"),

            //On appelle la fonction nbCredits qu'on retrouve plus bas
            'provider' => "PluginMorewidgetsCreditcards::nbCredits",

            //Cela signifie que cette carte peut être filtrée uniquement lorsqu'on utilise un filtre crédit
            'filters' => [
                'credit'
            ]
        ];

        $cards["bn_credit_used"] = [
            'widgettype' => ["bigNumber"],
            'itemtype' => "\\Credit",
            'group' => __('Credit'),
            'label' => __("Crédit utilisé"),
            'provider' => "PluginMorewidgetsCreditcards::nbCreditsUsed",
            'filters' => [
                'credit'
            ]
        ];


        //Carte qui compte le nombre de crédit restant
        $cards["bn_credit_remaining"] = [
            'widgettype' => ["bigNumber"],
            'itemtype' => "\\Credit",
            'group' => __('Credit'),
            'label' => __("Nombre de crédits restant"),
            'provider' => "PluginMorewidgetsCreditcards::nbCreditsRemaining",
            'filters' => [
                'credit'
            ]
        ];

        //Carte qui affiche le nombre de crédit restant
        $cards["bn_percent_credit_remaining"] = [
            'widgettype' => ["bigNumber"],
            'itemtype' => "\\Credit",
            'group' => __('Credit'),
            'label' => __("Pourcentage de crédits restant"),
            'provider' => "PluginMorewidgetsCreditcards::percentCreditsRemaining",
            'filters' => [
                'credit'
            ]
        ];

        //Carte qui affiche le nombre de crédit utilisé
        $cards["bn_percent_credit_used"] = [
            'widgettype' => ["bigNumber"],
            'itemtype' => "\\Credit",
            'group' => __('Credit'),
            'label' => __("Pourcentage de crédits utilisé"),
            'provider' => "PluginMorewidgetsCreditcards::percentCreditsUsed",
            'filters' => [
                'credit'
            ]
        ];

        $cards["date_end_ticket"] = [
            'widgettype' => ["bigNumber"],
            'itemtype' => "\\Credit",
            'group' => __('Credit'),
            'label' => __("Date de fin du contrat"),
            'provider' => "PluginMorewidgetsCreditcards::getDateEndingCredit",
            'filters' => [
                'credit'
            ]
        ];


        $cards["credits_consumption_ticket"] = [
            'widgettype' => ['bars', 'stackedbars', 'lines'],
            'itemtype' => "\\Credit",
            'group' => __('Credit'),
            'label' => __("Consommation de crédit par ticket"),
            'provider' => "PluginMorewidgetsCreditcards::getCreditsConsumption",
            'filters' => [
                'credit'
            ]
        ];


        $cards["credits_evolution_period"] = [
            'widgettype' => ['lines', 'bars', 'areas'],
            'itemtype' => "\\Credit",
            'group' => __('Credit'),
            'label' => __("Evolution de la consommation de crédit"),
            'provider' => "PluginMorewidgetsCreditcards::getCreditsEvolution",
            'filters' => [
                'credit'
            ]
        ];


        return $cards;
    }

    /**
     * Nombre de crédits initiaux
     * @param array $params
     * @return array
     */
    public static function nbCredits(array $params = []): array
    {

        $DB = DBConnection::getReadConnection();
        $default_params = [
            'label' => "",
            'icon' => PluginCreditEntity::getIcon(),
            'apply_filters' => [],
        ];
        $params = array_merge($default_params, $params);

        $t_table = PluginCreditEntity::getTable();

        //On ecrit la requête SQL
        $criteria = array_merge_recursive(
            [
                'SELECT' => [
                    'quantity'
                ],
                'FROM' => $t_table,

            ],

            //Cette fonction va permettre de filtrer en fonction du type de crédit sélectionné
            PluginMorewidgetsUtilities::getFiltersCriteria($t_table, $params['apply_filters'])
        );

        //On fait la requête auprès de la base de données
        $iterator = $DB->request($criteria);

        //On a recupère le resultat
        $result = $iterator->next();

        //Puis on attribue la valeur retournée à une variable.
        $nb_items = $result['quantity'];

        //On retourne un tableau
        return [

            //Le nombre qui va être affiché
            'number' => $nb_items,

            //url permet d'accéder à un lien en cliquand sur la carte, ici nous laissons cela vide
            'url' => '',

            //Le texte qui va être affiché en dessous le nombre
            'label' => 'Crédit initial',

            //L'icone accompagnant la carte
            'icon' => $default_params['icon'],
        ];
    }

    /**
     * Date de la fin du crédit en format MM/YY
     *
     * @param array $params default values for
     * - 'title' of the card
     * - 'icon' of the card
     * - 'apply_filters' values from dashboard filters
     *
     * @return array
     */
    public static function getDateEndingCredit(array $params = []): array
    {
        $DB = DBConnection::getReadConnection();
        $default_params = [
            'label' => "",
            'icon' => PluginCreditEntity::getIcon(),
            'apply_filters' => [],
        ];
        $params = array_merge($default_params, $params);

        $t_table = PluginCreditEntity::getTable();
        $criteria = array_merge_recursive(
            [
                'SELECT' => [
                    'end_date',
                ],
                'FROM' => $t_table,

            ],
            PluginMorewidgetsUtilities::getFiltersCriteria($t_table, $params['apply_filters'])
        );


        $iterator = $DB->request($criteria);
        $result = $iterator->next();
        $date = date_create($result['end_date']);
        $date = date_format($date, 'm/y');

        return [
            'number' => $date,
            'url' => '',
            'label' => 'Fin du contrat',
            'icon' => $default_params['icon'],
        ];
    }

    /**
     * Quantité des crédits utilisés
     *
     * @param array $params default values for
     * - 'title' of the card
     * - 'icon' of the card
     * - 'apply_filters' values from dashboard filters
     *
     * @return array
     */
    public static function nbCreditsUsed(array $params = []): array
    {
        $default_params = [
            'label' => "",
            'icon' => PluginCreditEntity::getIcon(),
            'apply_filters' => [],
        ];
        $params = array_merge($default_params, $params);

        $tab = self::getCredits($params);
        $nb_items = $tab['sum'];

        return [
            'number' => $nb_items,
            'url' => '',
            'label' => 'Crédit utilisé',
            'icon' => $default_params['icon'],
        ];
    }

    /**
     * Quantité de crédits restant
     *
     * @param array $params default values for
     * - 'title' of the card
     * - 'icon' of the card
     * - 'apply_filters' values from dashboard filters
     *
     * @return array
     */
    public static function nbCreditsRemaining(array $params = []): array
    {
        $default_params = [
            'label' => "",
            'icon' => PluginCreditEntity::getIcon(),
            'apply_filters' => [],
        ];
        $params = array_merge($default_params, $params);

        $tab = self::getCredits($params);
        $result = $tab['quantity'] - $tab['sum'];

        return [
            'number' => $result,
            'url' => '',
            'label' => 'Crédit restant ',
            'icon' => $default_params['icon'],
        ];
    }

    /**
     * Pourcentage des crédits utilisés
     * @param array $params
     * @return array
     */
    public static function percentCreditsUsed(array $params = []): array
    {
        $default_params = [
            'label' => "",
            'icon' => PluginCreditEntity::getIcon(),
            'apply_filters' => [],
        ];
        $params = array_merge($default_params, $params);

        $tab = self::getCredits($params);
        $result = ($tab['sum']) / $tab['quantity'] * 100;

        return [
            'number' => $result . '%',
            'url' => '',
            'label' => 'Crédit utilisé',
            'icon' => $default_params['icon'],
        ];
    }

    /**
     * Pourcentage des crédits restants
     * @param array $params
     * @return array
     */
    public static function percentCreditsRemaining(array $params = []): array
    {
        $default_params = [
            'label' => "",
            'icon' => PluginCreditEntity::getIcon(),
            'apply_filters' => [],
        ];
        $params = array_merge($default_params, $params);

        $tab = self::getCredits($params);
        $result = (($tab['quantity'] - $tab['sum']) / $tab['quantity']) * 100;

        return [
            'number' => $result . '%',
            'url' => '',
            'label' => 'Crédits restant',
            'icon' => $default_params['icon'],
        ];
    }

    /**
     * Consommation des crédits par tickets
     *
     * @param array $params default values for
     * - 'title' of the card
     * - 'icon' of the card
     * - 'apply_filters' values from dashboard filters
     *
     * @return array
     */
    public static function getCreditsConsumption(array $params = []): array
    {
        $DB = DBConnection::getReadConnection();
        $default_params = [
            'label' => "",
            'icon' => "",
            'apply_filters' => [],
        ];
        $params = array_merge($default_params, $params);

        $t_table = PluginCreditEntity::getTable();
        $s_table = PluginCreditTicket::getTable();
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
                'FROM' => $t_table,
                'INNER JOIN' => [
                    $s_table => [
                        'ON' => [
                            $s_table => 'plugin_credit_entities_id',
                            $t_table => 'id',
                        ],
                    ],
                ],
            ],
            PluginMorewidgetsUtilities::getFiltersCriteria($t_table, $params['apply_filters'])
        );

        $series = [];
        $tickets = [];
        $iterator = $DB->request($criteria);
        $i = 0;
        $tab = array();

        while ($row = $iterator->next()) {

            $id = $row['tickets_id'];

            $tab[$id] += $row['consumed'];

        }

        foreach ($tab as $ticket => $value) {

            $tickets[$i] = 'Ticket n°' . $ticket;
            $series['credit']['name'] = 'Crédit';
            $series['credit']['data'][$i] = [
                'value' => $value,
                'url' => Ticket::getFormURL() . "?id=" . $ticket
            ];
            $i++;
        }

        return [
            'data' => [
                'labels' => $tickets,
                'series' => array_values($series),
            ],
            'label' => $params['label'],
            'icon' => $params['icon'],
        ];
    }

    /**
     * Evolution de l'utilisation de crédit par mois
     *
     * @param array $params default values for
     * - 'title' of the card
     * - 'icon' of the card
     * - 'apply_filters' values from dashboard filters
     *
     * @return array
     */
    public static function getCreditsEvolution(array $params = []): array
    {
        $DB = DBConnection::getReadConnection();
        $default_params = [
            'label' => "",
            'icon' => "",
            'apply_filters' => [],
        ];


        $params = array_merge($default_params, $params);

        $t_table = PluginCreditEntity::getTable();
        $s_table = PluginCreditTicket::getTable();
        //Get the start date and the end date

        $criteria = array_merge_recursive(
            [
                'SELECT' => [
                    "$t_table.begin_date",
                    "$t_table.end_date",
                    "$s_table.consumed",
                    "$s_table.date_creation"
                ],
                'FROM' => $t_table,
                'INNER JOIN' => [
                    $s_table => [
                        'ON' => [
                            $s_table => 'plugin_credit_entities_id',
                            $t_table => 'id',
                        ],
                    ],
                ],

            ],
            PluginMorewidgetsUtilities::getFiltersCriteria($t_table, $params['apply_filters'])
        );

        $iterator = $DB->request($criteria);
        $result = $iterator->next();

        $i = 0;
        $monthsYears = [];
        $begin = date_create($result['begin_date']);
        $end = date_create($result['end_date']);


        $interval = DateInterval::createFromDateString('1 month');
        $period = new DatePeriod($begin, $interval, $end);

        foreach ($period as $dt) {
            $monthsYears[$i] = $dt->format('m/y');
            $i++;
        }


        $values = [];
        foreach ($monthsYears as $month) {
            $iterator = $DB->request($criteria);
            $z = 0;
            while ($row = $iterator->next()) {

                $date = date_create($row['date_creation']);
                $date = date_format($date, 'm/y');

                if ($month == $date) {

                    $values[$month][$z] = $row['consumed'];
                    $z++;
                } else {
                    $values[$month][$z] = 0;
                    $z++;
                }
            }
        }

        $total = array();
        $z = 0;
        foreach ($values as $value) {

            $sum = 0;
            foreach ($value as $v) {
                $sum += $v;
                unset($v);

            }
            $total['parmois']['name'] = 'Crédit utilisé par mois';
            $total['parmois']['data'][$z] = $sum;
            $z++;
        }

        $z = 0;

        $sum = 0;
        foreach ($values as $value) {
            foreach ($value as $v) {
                $sum += $v;
                unset($v);

            }
            $total['evolution']['name'] = 'Total accumulé';
            $total['evolution']['data'][$z] = $sum;
            $z++;
        }

        return [
            'data' => [
                'labels' => $monthsYears,
                'series' => array_values($total),
            ],
            'label' => $params['label'],
            'icon' => $params['icon'],
        ];
    }

    /**
     * Permet de recuperer le nombre de crédit utilisé et la quantité initiale
     */
    public static function getCredits(array $params = []): array
    {
        $DB = DBConnection::getReadConnection();


        $t_table = PluginCreditEntity::getTable();
        $s_table = PluginCreditTicket::getTable();
        $criteria = array_merge_recursive(
            [
                'SELECT' => [
                    "$t_table.quantity",
                    'SUM' => "$s_table.consumed AS sum",
                ],
                'FROM' => $t_table,
                'INNER JOIN' => [
                    $s_table => [
                        'ON' => [
                            $s_table => 'plugin_credit_entities_id',
                            $t_table => 'id',
                        ],
                    ],
                ],
            ],
            PluginMorewidgetsUtilities::getFiltersCriteria($t_table, $params['apply_filters'])
        );
        $iterator = $DB->request($criteria);
        $result = $iterator->next();

        return [
            'quantity' => $result['quantity'],
            'sum'      => $result['sum'],
        ];
    }
}