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


        $squestion_obj = new PluginSatisfactionSurveyQuestion;
        $pluginSatisfactionSurveyDBTM         = new PluginSatisfactionSurvey();

        $surveys = $pluginSatisfactionSurveyDBTM->find(['is_active' => true]);


        foreach($surveys as $survey)
        {
            foreach ($squestion_obj->find(['plugin_satisfaction_surveys_id' => $survey['id']]) as $question) {


                $cards['averageMoreSatisfaction' . $question['name']] = [
                    'widgettype' => ['bigNumber'],
                    'itemtype' => "\\Satisfaction",
                    'group' => __('Satisfaction'),
                    'label' => __("Moyenne More Satisfaction : ". $question['name']),
                    'provider' => "PluginMorewidgetsSatisfactioncards::averageMoreSatisfaction".$question['type'],
                    'args' => [
                        'id'    => $question['id'],
                    ],
                    'filters' => []
                ];


                $cards['countMoreSatisfcation' . $question['name']] = [
                    'widgettype' => ['summaryNumbers', 'multipleNumber', 'bar', 'donut', 'pie'],
                    'itemtype' => "\\Satisfaction",
                    'group' => __('Satisfaction'),
                    'label' => __("Count More Satisfaction : ". $question['name']),
                    'provider' => "PluginMorewidgetsSatisfactioncards::countMoreSatisfaction".$question['type'],
                    'args' => [
                        'id'    => $question['id'],
                    ],
                    'filters' => []
                ];
            }
        }

        return $cards;
    }

    public static function averageMoreSatisfactionnote(string $id = "", array $params = []): array
    {
        $DB = DBConnection::getReadConnection();

        $default_params = [
            'label'         => "",
            'icon'          => self::getIconStar(),
            'apply_filters' => [],
        ];

        $params = array_merge($default_params, $params);

        $t_table  = PluginSatisfactionSurveyAnswer::getTable();

        $criteria = array_merge_recursive(
            [
                'SELECT' => [
                    'ticketsatisfactions_id',
                    'answer',
                ],
                'FROM' => $t_table,
            ],
            PluginMorewidgetsUtilities::getFiltersCriteria($t_table, $params['apply_filters'])
        );

        $value = 0;
        $question_obj = new PluginSatisfactionSurveyQuestion;
        $obj_survey_answer = new PluginSatisfactionSurveyAnswer();
-       $dbu = new DbUtils();
        $iterator = $DB->request($criteria);

        $total = 0;
        while($data = $iterator->next()) {

            $answers = $dbu->importArrayFromDB($data['answer']);

            foreach ($answers as $questions_id => $answer) {
                if($questions_id == $id)
                {
                    $total++;
                    $question_obj->getFromDB($questions_id);

                    $value += $obj_survey_answer->getAnswer($question_obj->fields, $answer);

                }
            }
        }


        $value = $value / $total;

        return [
            'number' => $value,
            'url'    => '',
            'label'  => 'Satisfaction globale',
            'icon'   => $default_params['icon'],
        ];

    }

    public static function averageMoreSatisfactionyesno(string $id = "", array $params = []): array
    {
        $DB = DBConnection::getReadConnection();

        $default_params = [
            'label'         => "",
            'icon'          => self::getIconStar(),
            'apply_filters' => [],
        ];

        $params = array_merge($default_params, $params);

        $t_table  = PluginSatisfactionSurveyAnswer::getTable();

        $criteria = array_merge_recursive(
            [
                'SELECT' => [
                    'ticketsatisfactions_id',
                    'answer',
                ],
                'FROM' => $t_table,
            ],
            PluginMorewidgetsUtilities::getFiltersCriteria($t_table, $params['apply_filters'])
        );

        $question_obj = new PluginSatisfactionSurveyQuestion;
        $obj_survey_answer = new PluginSatisfactionSurveyAnswer();
        $dbu = new DbUtils();
        $iterator = $DB->request($criteria);

        $total = 0;
        $nb1 = 0;

        while($data = $iterator->next()) {

            $answers = $dbu->importArrayFromDB($data['answer']);

            foreach ($answers as $questions_id => $answer) {
                if($questions_id == $id)
                {
                    $total++;
                    $question_obj->getFromDB($questions_id);

                    if($obj_survey_answer->getAnswer($question_obj->fields, $answer) == 'Oui') {
                        $nb1++;
                    }
                }
            }
        }

        $value = ($nb1 / $total) * 100;

        return [
            'number' => $value,
            'url'    => '',
            'label'  => 'Satisfaction globale',
            'icon'   => $default_params['icon'],
        ];

    }

    public static function countMoreSatisfactionnote(string $id = "", array $params = []): array
    {

        $DB = DBConnection::getReadConnection();

        $default_params = [
            'label' => "",
            'icon' => self::getIconStar(),
            'apply_filters' => [],
        ];
        $params = array_merge($default_params, $params);

        $t_table  = PluginSatisfactionSurveyAnswer::getTable();
        $u_table  = PluginSatisfactionSurveyQuestion::getTable();


        $where = [
            "$u_table.id" => $id,
        ];



        $criteria = array_merge_recursive(
            [
                'SELECT' => [
                    'number'
                ],
                'FROM' => $u_table,
                'WHERE' => $where,
            ],
            PluginMorewidgetsUtilities::getFiltersCriteria($t_table, $params['apply_filters'])
        );


        $question_obj = new PluginSatisfactionSurveyQuestion;
        $obj_survey_answer = new PluginSatisfactionSurveyAnswer();
        $dbu = new DbUtils();

        $iterator = $DB->request($criteria);
        $result = $iterator->next();

        $nbMax = $result['number'];
        $value = array();

        $i = 1;
        while($i != $nbMax) {
            $value[$i]['data']['value'] = 0;
            $i++;
        }

        $criteria = array_merge_recursive(
            [
                'SELECT' => [
                    'ticketsatisfactions_id',
                    'answer',
                ],
                'FROM' => $t_table,
            ],
            PluginMorewidgetsUtilities::getFiltersCriteria($t_table, $params['apply_filters'])
        );

        $iterator = $DB->request($criteria);
        while($data = $iterator->next()) {

            $answers = $dbu->importArrayFromDB($data['answer']);

            foreach ($answers as $questions_id => $answer) {
                if($questions_id == $id)
                {
                    $question_obj->getFromDB($questions_id);

                    $value[$obj_survey_answer->getAnswer($question_obj->fields, $answer)]['data']['value']++;
                }
            }
        }


        return [
            'data' => [
                [
                    'number' => $value[1]['data']['value'],
                    'label' => __("1 étoiles"),
                    'url' => $value[1]['data']['url'],
                    'color' => '#FB0000',
                ], [
                    'number' => $value[2]['data']['value'],
                    'label' => __("2 étoiles"),
                    'url' => $value[2]['data']['url'],
                    'color' => '#C42D77',
                ], [
                    'number' => $value[3]['data']['value'],
                    'label' => __("3 étoiles"),
                    'url' => $value[3]['data']['url'],
                    'color' => '#2D75C4',
                ], [
                    'number' => $value[4]['data']['value'],
                    'label' => __("4 étoiles"),
                    'url' => $value[4]['data']['url'],
                    'color' => '#2DC47F',
                ], [
                    'number' => $value[5]['data']['value'],
                    'label' => __("5 étoiles"),
                    'url' => $value[5]['data']['url'],
                    'color' => '#36C42D',
                ]
            ],
            'label' => $params['label'],
            'icon' => $params['icon'],
        ];
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
        $value = array();

        $s_criteria = [
            'criteria' => [
                [
                    'link'       => 'AND',
                    'field'      =>  62,// satisfaction
                    'searchtype' => 'contains',
                    'value'      => null,
                ],
            ]
        ];

        $i = 1;
        while($i != 6)
        {
            $value[$i]['data']['value'] = 0;
            $i++;
        }

        foreach ($iterator as $result) {
            $s_criteria['criteria'][0]['value'] = $result['satisfaction'];
            $value[$result['satisfaction']]['data'] = [
                'value' => $result['total'],
                'url'   => Ticket::getSearchURL() . "?" . Toolbox::append_params($s_criteria),
            ];
        }

        return [
            'data' => [
                [
                    'number' => $value[1]['data']['value'],
                    'label' => __("1 étoiles"),
                    'url' => $value[1]['data']['url'],
                    'color' => '#FB0000',
                ], [
                    'number' => $value[2]['data']['value'],
                    'label' => __("2 étoiles"),
                    'url' => $value[2]['data']['url'],
                    'color' => '#C42D77',
                ], [
                    'number' => $value[3]['data']['value'],
                    'label' => __("3 étoiles"),
                    'url' => $value[3]['data']['url'],
                    'color' => '#2D75C4',
                ], [
                    'number' => $value[4]['data']['value'],
                    'label' => __("4 étoiles"),
                    'url' => $value[4]['data']['url'],
                    'color' => '#2DC47F',
                ], [
                    'number' => $value[5]['data']['value'],
                    'label' => __("5 étoiles"),
                    'url' => $value[5]['data']['url'],
                    'color' => '#36C42D',
                ]
            ],
            'label' => $params['label'],
            'icon' => $params['icon'],
        ];
    }

    public static function countMoreSatisfactionyesno(string $id = "", array $params = []): array
    {

        $DB = DBConnection::getReadConnection();

        $default_params = [
            'label' => "",
            'icon' => self::getIconStar(),
            'apply_filters' => [],
        ];
        $params = array_merge($default_params, $params);

        $t_table  = PluginSatisfactionSurveyAnswer::getTable();

        $question_obj = new PluginSatisfactionSurveyQuestion;
        $obj_survey_answer = new PluginSatisfactionSurveyAnswer();
        $dbu = new DbUtils();

        $criteria = array_merge_recursive(
            [
                'SELECT' => [
                    'ticketsatisfactions_id',
                    'answer',
                ],
                'FROM' => $t_table,
            ],
            PluginMorewidgetsUtilities::getFiltersCriteria($t_table, $params['apply_filters'])
        );

        $value = array();
        
        $iterator = $DB->request($criteria);
        while($data = $iterator->next()) {

            $answers = $dbu->importArrayFromDB($data['answer']);

            foreach ($answers as $questions_id => $answer) {
                if($questions_id == $id)
                {
                    $question_obj->getFromDB($questions_id);

                    $value[$obj_survey_answer->getAnswer($question_obj->fields, $answer)]['data']['value']++;
                }
            }
        }

        return [
            'data' => [
                [
                    'number' => $value['Oui']['data']['value'],
                    'label' => __("Oui"),
                    'url' => $value[1]['data']['url'],
                    'color' => '#FB0000',
                ], [
                    'number' => $value['Non']['data']['value'],
                    'label' => __("Non"),
                    'url' => $value[2]['data']['url'],
                    'color' => '#C42D77',
                ],
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