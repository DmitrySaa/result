<?php

namespace Testcasefusion;
use Bitrix\Main\ModuleManager;
use Bitrix\Main\Loader;
use Bitrix\Main\Config\Option;

class Creator
{
    public static function CreateSmartProcess($moduleId)
    {
        if (!Loader::includeModule('crm')) {
            return false;
        }

        try {
            // Фиксированный entity_type_id = 128
            $entityTypeId = 128;

            $data = [
                'NAME' => 'Test Cases',
                'TITLE' => 'Тест-кейсы',
                'CODE' => 'TEST_CASES_FUSION',
                'ENTITY_TYPE_ID' => $entityTypeId,
                'IS_STAGES_ENABLED' => true,
                'IS_BIZ_PROC_ENABLED' => true,
                'IS_USE_IN_USERFIELD_ENABLED' => true,
            ];

            // Создаем через TypeTable
            if (class_exists('Bitrix\Crm\Model\Dynamic\TypeTable')) {
                $result = \Bitrix\Crm\Model\Dynamic\TypeTable::add($data);

                if ($result->isSuccess()) {
                    $typeId = $result->getId();

                    // Сохраняем ID для будущего использования
                    Option::set($moduleId, 'smart_process_type_id', $typeId);
                    Option::set($moduleId, 'smart_process_entity_type_id', $entityTypeId);

                    // Создаем поля
                    $fieldsResult = self::CreateFields($typeId);

                    // Создаем бизнес-процесс (если доступен модуль bizproc)
                    if (Loader::includeModule('bizproc')) {
                        $bpResult = self::CreateBusinessProcess($typeId, $entityTypeId);
                    } else {
                        file_put_contents(
                            $_SERVER['DOCUMENT_ROOT'] . '/testcasefusion_install.log',
                            date('Y-m-d H:i:s') . " - INFO: Bizproc module not available, skipping business process creation\n",
                            FILE_APPEND
                        );
                    }

                    return true;
                } else {
                    $errors = $result->getErrorMessages();
                    file_put_contents(
                        $_SERVER['DOCUMENT_ROOT'] . '/testcasefusion_install.log',
                        date('Y-m-d H:i:s') . " - ERROR TypeTable: " . implode(", ", $errors) . "\n",
                        FILE_APPEND
                    );
                }
            }
        } catch (Exception $e) {
            file_put_contents(
                $_SERVER['DOCUMENT_ROOT'] . '/testcasefusion_install.log',
                date('Y-m-d H:i:s') . " - EXCEPTION: " . $e->getMessage() . "\n",
                FILE_APPEND
            );
            return false;
        }
    }

    public static function CreateFields($typeId)
    {
        if (!Loader::includeModule('crm')) {
            return false;
        }

        try {
            // Получаем тип для получения кода
            $type = \Bitrix\Crm\Model\Dynamic\TypeTable::getById($typeId)->fetch();
            if (!$type) {
                return false;
            }

            $entityId = 'CRM_' . $typeId;

            $userTypeEntity = new \CUserTypeEntity();

            // Поле "Описание"
            $descriptionField = [
                'ENTITY_ID' => $entityId,
                'FIELD_NAME' => 'UF_CRM_DESCRIPTION',
                'USER_TYPE_ID' => 'string',
                'XML_ID' => 'TCF_DESCRIPTION',
                'SORT' => 100,
                'MULTIPLE' => 'N',
                'MANDATORY' => 'N',
                'SHOW_FILTER' => 'I',
                'SHOW_IN_LIST' => 'Y',
                'EDIT_IN_LIST' => 'Y',
                'IS_SEARCHABLE' => 'Y',
                'EDIT_FORM_LABEL' => ['ru' => 'Описание'],
                'LIST_COLUMN_LABEL' => ['ru' => 'Описание'],
            ];
            $descResult = $userTypeEntity->Add($descriptionField);

            // Поле "Статус"
            $statusField = [
                'ENTITY_ID' => $entityId,
                'FIELD_NAME' => 'UF_CRM_STATUS',
                'USER_TYPE_ID' => 'enumeration',
                'XML_ID' => 'TCF_STATUS',
                'SORT' => 200,
                'MULTIPLE' => 'N',
                'MANDATORY' => 'N',
                'SHOW_FILTER' => 'I',
                'SHOW_IN_LIST' => 'Y',
                'EDIT_IN_LIST' => 'Y',
                'IS_SEARCHABLE' => 'Y',
                'EDIT_FORM_LABEL' => ['ru' => 'Статус'],
                'LIST_COLUMN_LABEL' => ['ru' => 'Статус'],
            ];
            $statusFieldId = $userTypeEntity->Add($statusField);

            if ($statusFieldId) {
                $enum = new \CUserFieldEnum();
                $enum->SetEnumValues($statusFieldId, [
                    'n0' => ['VALUE' => 'Новый', 'DEF' => 'Y', 'SORT' => 100],
                    'n1' => ['VALUE' => 'В работе', 'DEF' => 'N', 'SORT' => 200],
                    'n2' => ['VALUE' => 'Завершен', 'DEF' => 'N', 'SORT' => 300]
                ]);
            }

            // Поле "Приоритет"
            $priorityField = [
                'ENTITY_ID' => $entityId,
                'FIELD_NAME' => 'UF_CRM_PRIORITY',
                'USER_TYPE_ID' => 'enumeration',
                'XML_ID' => 'TCF_PRIORITY',
                'SORT' => 300,
                'MULTIPLE' => 'N',
                'MANDATORY' => 'N',
                'SHOW_FILTER' => 'I',
                'SHOW_IN_LIST' => 'Y',
                'EDIT_IN_LIST' => 'Y',
                'IS_SEARCHABLE' => 'Y',
                'EDIT_FORM_LABEL' => ['ru' => 'Приоритет'],
                'LIST_COLUMN_LABEL' => ['ru' => 'Приоритет'],
            ];
            $priorityFieldId = $userTypeEntity->Add($priorityField);

            if ($priorityFieldId) {
                $enum = new \CUserFieldEnum();
                $enum->SetEnumValues($priorityFieldId, [
                    'n0' => ['VALUE' => 'Низкий', 'DEF' => 'Y', 'SORT' => 100],
                    'n1' => ['VALUE' => 'Средний', 'DEF' => 'N', 'SORT' => 200],
                    'n2' => ['VALUE' => 'Высокий', 'DEF' => 'N', 'SORT' => 300]
                ]);
            }

            return true;
        } catch (Exception $e) {
            file_put_contents(
                $_SERVER['DOCUMENT_ROOT'] . '/testcasefusion_install.log',
                date('Y-m-d H:i:s') . " - EXCEPTION in CreateFields: " . $e->getMessage() . "\n",
                FILE_APPEND
            );
            return false;
        }
    }

    public static function CreateBusinessProcess($typeId, $entityTypeId)
    {
        try {

            $documentType = ['crm', \Bitrix\Crm\Integration\BizProc\Document\Dynamic::class, 'DYNAMIC_' . $entityTypeId];

            // $template = [                                                // 1.//отправка уведомления
            //     'DOCUMENT_TYPE' => $documentType,
            //     'AUTO_EXECUTE' => 1, //при создании
            //     'NAME' => 'Уведомление о новом тест-кейсе',
            //     'DESCRIPTION' => 'Отправляет уведомление пользователю при создании тест-кейса',
            //     'TEMPLATE' => [
            //         [
            //             'Type' => 'SequentialWorkflowActivity',
            //             'Name' => 'Template',
            //             'Properties' => [
            //                 'Title' => 'Обработка тест-кейса',
            //                 'Permission' => []
            //             ],
            //             'Children' => [
            //                 [
            //                     'Type' => 'IMNotifyActivity',
            //                     'Name' => 'SendNotification',
            //                     'Properties' => [
            //                         'MessageUserTo' => ['user_69581'],  // Получатель в массиве
            //                         'MessageType' => 2,                 // Тип уведомления
            //                         'MessageUserFrom' => [1],           // Отправитель в массиве
            //                         'Title' => 'Отправка уведомления',       // Заголовок уведомления
            //                         'MessageSite' =>                        // Текст
            //                             "Создан новый тест-кейс:\n" .
            //                             "Название: {=Document:TITLE}\n" .
            //                             "Приоритет: {=Document:UF_CRM_PRIORITY}\n" .
            //                             "Ссылка: {=Document:URL}"               
            //                     ]
            //                 ]
            //             ]
            //         ]
            //     ],
            //     'PARAMETERS' => [],
            //     'VARIABLES' => [],
            //     'CONSTANTS' => [],
            //     'USER_ID' => 1,
            //     'MODIFIER_USER' => 1,
            // ];


            $template = [                               // 2.//отправка почты
                'DOCUMENT_TYPE' => $documentType,
                'AUTO_EXECUTE' => 1, // при создании
                'NAME' => 'Уведомление о новом тест-кейсе',
                'DESCRIPTION' => 'Отправляет уведомление на почту',
                'TEMPLATE' => [
                    [
                        'Type' => 'SequentialWorkflowActivity',
                        'Name' => 'activity',
                        'Properties' => [],
                        'Children' => [
                            // Отправка email уведомления
                            [
                                'Type' => 'MailActivity',
                                'Name' => 'SendNotification',
                                'Properties' => [
                                    'MailSubject' => 'Создан новый тест-кейс: {=Document:TITLE}',
                                    'MailText' =>
                                    "Был создан новый тест-кейс:\n\n" .
                                        "Название: {=Document:TITLE}\n" .
                                        "Приоритет: {=Document:UF_CRM_PRIORITY}\n" .
                                        "Ссылка: {=Document:URL}\n\n",
                                    'MailMessageType' => 'plain',
                                    'MailCharset' => 'UTF-8',
                                    'MailUserFrom' => 1,
                                    'MailUserTo' => 1
                                ]
                            ],

                            // Запись в журнал после отправки
                            [
                                'Type' => 'LogActivity',
                                'Name' => 'LogAfterNotification',
                                'Properties' => [
                                    'Text' =>
                                    "Уведомление отправлено пользователю с id=1\n" .
                                        "Элемент: {=Document:TITLE}\n" .
                                        "Приоритет: {=Document:UF_CRM_PRIORITY}\n" .
                                        "Время отправки: {=System:Now}"
                                ]
                            ],
                        ]
                    ]
                ],
                'PARAMETERS' => [],
                'VARIABLES' => [],
                'CONSTANTS' => [],
                'USER_ID' => 1,
                'MODIFIER_USER' => 1,
            ];

            $result = \CBPWorkflowTemplateLoader::Add($template);

            return $result;
        } catch (Exception $e) {
            file_put_contents(
                $_SERVER['DOCUMENT_ROOT'] . '/testcasefusion_install.log',
                date('Y-m-d H:i:s') . " - EXCEPTION in CreateBusinessProcess: " . $e->getMessage() . "\n",
                FILE_APPEND
            );
            return false;
        }
    }
}
