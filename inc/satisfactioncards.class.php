<?php


class PluginMorewidgetsSatisfactioncards extends CommonDBTM
{
    public static function satisfactionCards(): array
    {
        $cards['averageSatisfaction'] = [
            'widgettype' => ["bigNumber"],
            'itemtype' => "\\Satisfaction",
            'group' => __('Satisfaction'),
            'label' => __("Moyenne des avis"),
            'provider' => "PluginMorewidgetsSatisfactioncards::averageSatisfaction",
            'filters' => []
        ];

        $cards['sumSatisfaction'] = [
            'widgettype' => ["bigNumber"],
            'itemtype' => "\\Satisfaction",
            'group' => __('Satisfaction'),
            'label' => __("Total d'avis"),
            'provider' => "PluginMorewidgetsSatisfactioncards::sumSatisfaction",
            'filters' => []
        ];

        $cards['countSatisfaction'] = [
            'widgettype' => ['summaryNumbers', 'multipleNumber', 'bar', 'donut', 'pie'],
            'itemtype' => "\\Satisfaction",
            'group' => __('Satisfaction'),
            'label' => __("Repartition des avis"),
            'provider' => "PluginMorewidgetsSatisfactioncards::countSatisfaction",
            'filters' => []
        ];

        return $cards;
    }

    public static function averageSatisfaction(array $params = []): array
    {
        $DB = DBConnection::getReadConnection();

        $default_params = [
            'label'         => "",
            'icon'          => self::getIconStar(),
            'apply_filters' => [],
        ];

        $params = array_merge($default_params, $params);

        $t_table = TicketSatisfaction::getTable();

        $criteria = array_merge_recursive(
            [
                'SELECT' => [
                    'satisfaction',
                ],
                'FROM' => $t_table,
            ],
            PluginMorewidgetsUtilities::getFiltersCriteria($t_table, $params['apply_filters'])
        );

        $iterator = $DB->request($criteria);

        $total = 0;
        $somme = 0;
        foreach($iterator as $result) {
            $total++;
            $somme += $result['satisfaction'];
        }
        $somme = $somme / $total;

        return [
            'number' => $somme,
            'url'    => '',
            'label'  => 'Satisfaction globale',
            'icon'   => $default_params['icon'],
        ];
    }

    public static function sumSatisfaction(array $params = []): array
    {
        $DB = DBConnection::getReadConnection();

        $default_params = [
            'label'         => "",
            'icon'          => self::getIconStar(),
            'apply_filters' => [],
        ];

        $params = array_merge($default_params, $params);

        $t_table = TicketSatisfaction::getTable();

        $criteria = array_merge_recursive(
            [
                'SELECT' => [
                    'COUNT' => "$t_table.satisfaction AS sum",
                ],
                'FROM' => $t_table,
            ],
            PluginMorewidgetsUtilities::getFiltersCriteria($t_table, $params['apply_filters'])
        );

        $iterator = $DB->request($criteria);
        $result = $iterator->next();

        return [
            'number' => $result['sum'],
            'url'    => '',
            'label'  => 'Total de réponses',
            'icon'   => $default_params['icon'],
        ];
    }

    public static function countSatisfaction(array $params = []): array
    {

        $DB = DBConnection::getReadConnection();

        $default_params = [
            'label' => "",
            'icon' => self::getIconStar(),
            'apply_filters' => [],
        ];
        $params = array_merge($default_params, $params);

        $t_table = TicketSatisfaction::getTable();

        $criteria = array_merge_recursive(
            [
                'SELECT' => [
                    'COUNT' => 'satisfaction as total',
                    'satisfaction',
                ],
                'FROM' => $t_table,
                'GROUP' => "$t_table.satisfaction",
            ]
        );

        $iterator = $DB->request($criteria);
        $value = array(0, 0, 0, 0, 0);

        foreach ($iterator as $result) {
            $value[$result['satisfaction']] = $result['total'];
        }

        return [
            'data' => [
                [
                    'number' => $value[1],
                    'label' => __("1 étoiles"),
                    'url' => '',
                    'color' => '#FB0000',
                ], [
                    'number' => $value[2],
                    'label' => __("2 étoiles"),
                    'url' => '',
                    'color' => '#C42D77',
                ], [
                    'number' => $value[3],
                    'label' => __("3 étoiles"),
                    'url' => '',
                    'color' => '#2D75C4',
                ], [
                    'number' => $value[4],
                    'label' => __("4 étoiles"),
                    'url' => '',
                    'color' => '#2DC47F',
                ], [
                    'number' => $value[5],
                    'label' => __("5 étoiles"),
                    'url' => '',
                    'color' => '#36C42D',
                ]
            ],
            'label' => $params['label'],
            'icon' => $params['icon'],
        ];
    }

    static function getIconStar(): string
    {
        return "far fa-star";
    }
}