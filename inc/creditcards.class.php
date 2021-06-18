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

        //Carte qui compte le nombre de crédit initial
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
        //On récupère la configuration pour ce connecter à la BDD
        $DB = DBConnection::getReadConnection();

        //Paramètres par défaut
        $default_params = [
            //Texte du widget
            'label' => "",
            //Icône du widget
            'icon' => PluginCreditEntity::getIcon(),
            //Filtres appliqués
            'apply_filters' => [],
        ];
        $params = array_merge($default_params, $params);

        //On récupère le nom de la table crédit
        $t_table = PluginCreditEntity::getTable();

        //On écrit la requête SQL
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

        //On récupère le resultat
        $result = $iterator->next();

        //Puis on attribue la valeur retournée à une variable.
        $nb_items = $result['quantity'];

        //On retourne un tableau
        return [

            //Le nombre qui va être affiché
            'number' => $nb_items,

            //url permet d'accéder à un lien en cliquant sur la carte, ici nous le laissons vide
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

        //On récupère le nombre de crédits utilisé
        $tab = self::getCredits($params);

        return [
            'number' => $tab['sum'],
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

        //On récupère la quantité de base des crédits et la somme utilisée
        $tab = self::getCredits($params);

        //On calcule le nombre de crédits restant
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

        //On récupère la quantité de base des crédits et la somme utilisée
        $tab = self::getCredits($params);

        //On calcule le pourcentage utilisé
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

        //On récupère la quantité de base des crédits et la somme utilisée
        $tab = self::getCredits($params);

        //On calcule le pourcentage restant
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

        //Pour chaque ligne resultant de la requête
        while ($row = $iterator->next()) {

            //On ajoute au tableau $tab avec comme clé le nom du ticket pour pouvoir avoir le total de crédits utilisés
            $tab[$row['tickets_id']] += $row['consumed'];
        }

        //Pour chaque clé dans le tableau
        foreach ($tab as $ticket => $value) {

            //Ce sera
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

        $t_table = PluginCreditTicket::getTable();

        $where = [
            "$t_table.plugin_credit_entities_id" => $params['apply_filters'],
        ];

        $criteria = [
            'SELECT' => [
                new QueryExpression(
                    "FROM_UNIXTIME(UNIX_TIMESTAMP(" . $DB->quoteName("{$t_table}.date_creation") . "),'%Y-%m') AS period"
                ),
                new QueryExpression(
                    "SUM(IFNULL({$t_table}.consumed, 0))
                    as ". $DB->quoteValue(_x('status', 'consumed'))
                ),
            ],
            'FROM'  => $t_table,
            'ORDER' => 'period ASC',
            'GROUP' => ['period'],
            'WHERE' => $where,
        ];

        $monthYears = [];
        $series = [];
        $iterator = $DB->request($criteria);

        $i = 0;
        foreach ($iterator as $result) {

            $monthYears[] = $result['period'];
            $tmp = $result;

            unset($tmp['period']);

            foreach ($tmp as $value) {


                $series['parmois']['name'] = "Crédit utilisé par mois";
                $series['parmois']['data'][] = [
                    'value' => (int)$value,
                    'url' => '',
                ];

                $series['total']['name'] = "Total accumulé";
                if ($i > 0)
                {
                    $series['total']['data'][] = [
                        'value' => (int)$value + $series['total']['data'][$i-1]['value'],
                        'url' => '',
                    ];
                }
                else {
                    $series['total']['data'][] = [
                        'value' => (int)$value,
                        'url' => '',
                    ];
                }
            }
            $i++;
        }

        return [
            'data'  => [
                'labels' => $monthYears,
                'series' => array_values($series),
            ],
            'label' => $params['label'],
            'icon'  => $params['icon'],
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