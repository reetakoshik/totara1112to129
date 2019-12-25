<?php

namespace GoLearningZone\Partials;

use GoLearningZone\Traits\Theme as ThemeTrait;
use GoLearningZone\Settings\Header as HeaderSettings;
use GoLearningZone\Settings\Theme as ThemeSettings;
use tool_usertours\manager;

const PREVIEW_MESSAGE_LENGH = 200;

class Header extends Base
{
    use ThemeTrait;

    public function render()
    {
        global $CFG, $USER, $PAGE; 
        $renderer = $this->renderer;
       
        $notificationsInfo = $this->getSettingValue(HeaderSettings::NOTIFICATIONS) == 1 ? $this->getNotifications() : false;
        
        $messagesInfo = $this->getSettingValue(HeaderSettings::MESSAGES) == 1 ? $this->getMessages() : false;
        $badgesInfo = $this->getSettingValue(HeaderSettings::BADGES) == 1 ? $this->getBadges() : false;

        $a = $renderer->render_from_template(
            'theme_golearningzone/header',
            [
                'siteurl'       => $CFG->wwwroot,
                'logo'          => $this->getLogo(), 
                'user_menu'     => $renderer->user_menu(),
                'totaramenu'    => $this->getTotaraMenu(),
                'sesskey'       => sesskey(),
                'right_to_left' => right_to_left(),
                'color'         => $this->getSettingValue(ThemeSettings::COLOR),
//                'alerts'        => $this->getSettingValue(HeaderSettings::ALERTS) == 1 ? [
//                    'my_alerts'     => get_string('my_alerts', 'theme_golearningzone'),
//                    'list'          => $alertsInfo['alerts'],
//                    'total_alerts'  => $alertsInfo['total'],
//                    'total_alerts_string' => get_string(
//                        'total_alerts',
//                        'theme_golearningzone',
//                        '<span class="count">'.$alertsInfo['total'].'</span>'
//                    ),
//                ] : false,
                
                'notification_checkurl' => $CFG->wwwroot.'/message/notificationpreferences.php?userid='.$USER->id,
                'notification_editurl'  => $CFG->wwwroot.'/message/notificationpreferences.php?userid='.$USER->id,
                'message_editurl'       => $CFG->wwwroot.'/message/edit.php?id='.$USER->id,
                'adminmenu' =>$this->getTotaraMenu123(),
                'notifications'                 => $this->getSettingValue(HeaderSettings::NOTIFICATIONS) == 1 ? [
                    'my_notifications'          => get_string('my_notifications', 'theme_golearningzone'),
                    'list'                      => $notificationsInfo['notifications'],
                    'total_notifications'       => $notificationsInfo['total'],
                    'total_notifications_string' => get_string(
                        'total_notifications',
                        'theme_golearningzone'
                    ),
                ] : false,
                'messages'      => $this->getSettingValue(HeaderSettings::MESSAGES) == 1 ? [
                    'my_messages'    => get_string('my_messages', 'theme_golearningzone'),
                    'list'           => $messagesInfo['messages'],
                    'total_messages' => $messagesInfo['total'],
                    'total_messages_string' => get_string(
                        'total_messages', 
                        'theme_golearningzone', 
                        '<span class="count">'.$messagesInfo['total'].'</span>'
                    ),
                ] : false,
                'badges'        =>  $this->getSettingValue(HeaderSettings::BADGES) == 1 ? [
                    'my_badges'     => get_string('my_badges', 'theme_golearningzone'),
                    'list'          => $badgesInfo['badges'],
                    'total_badges'  => $badgesInfo['total'],
                    'total_badges_string' => get_string(
                        'total_badges', 
                        'theme_golearningzone', 
                        '<span class="count">'.$badgesInfo['total'].'</span>'
                    ),
                ] : false,
                'search' => [
                    'enabled' => $this->getSettingValue(HeaderSettings::SEARCH) == 1,
                    'text'    => [
                        'header'      => get_string('activity-search', 'theme_golearningzone'),
                        'placeholder' => get_string('search', 'totara_core'),
                        'submit'      => get_string('search', 'totara_core'),
                    ]
                ],
                'tour' => [
                    'enabled' => manager::get_current_tour() ? true : false,
                    'tooltip' => get_string('resettouronpage', 'theme_golearningzone')
                ]
            ]
        );
        //echo "<pre>"; print_r($notificationsInfo); die('hiii');
        return $a;
    }  

    private function getAlerts()
    {
        $icons = [
            'facetoface-add' => '<i class="fa fa-calendar" aria-hidden="true"></i>',
            'default'        => '<i class="fa fa-sitemap" aria-hidden="true"></i>'
        ];

        $alerts = tm_messages_get('totara_alert', 'timecreated DESC ', false, true);

        foreach ($alerts as $num => $alert) {
            $alerts[$num] = (array)$alert;
            if (isset($alerts[$num]['icon']) && isset($icons[$alerts[$num]['icon']])) {
                $alerts[$num]['icon'] = $icons[$alerts[$num]['icon']];
            } else {
                $alerts[$num]['icon'] = $icons['default'];
            }
        }

        $alerts = array_values($alerts);
        
        return [
            'alerts' => array_slice($alerts, 0, 2),
            'total'  => tm_messages_count('totara_alert', false)
        ];
    }

    /**
     * Retrieve user's notifications for menu in header
     * @return array
     */
    private function getNotifications()
    {
        global $USER;

        $notifications = \message_popup\api::get_popup_notifications($USER->id, 'DESC', 0, 0);

        foreach ($notifications as &$n) {
            $n->icon =  '<i class="fa fa-file-text" aria-hidden="true"></i>';
        }

        return [
            'notifications' => array_values($notifications),
            'total'  => \message_popup\api::count_unread_popup_notifications($USER->id)
        ];
    }

    /**
     * Retrieve user's messages for menu in header
     * @return array
     */
    private function getMessages()
    {
        global $USER, $DB;

        $response = [
            'messages' => [],
            'total'    => 0
        ];

        if ($USER->id) {

            $response['messages'] = $DB->get_counted_records_sql("
              SELECT m.id AS mid, m.smallmessage, m.fullmessagehtml, m.useridfrom, u.picture, u.firstname, u.lastname FROM {message} m
                LEFT JOIN {message_metadata} md ON md.messageid = m.id AND md.id IS NULL
                INNER JOIN {user} u ON m.useridfrom = u.id
              WHERE  m.useridto = ?
                AND m.timeusertodeleted = 0
                AND m.notification <> 1
              ORDER BY m.timecreated DESC",
                [$USER->id], 0, 0, $response['total']);

            foreach ($response['messages'] as &$msg) {
                $smallmessage = trim($msg->smallmessage);
                $fulltext = trim(strip_tags($msg->fullmessagehtml), " \t\n\r\0\x0B-");
                $fulltext = substr($fulltext, 0, 70).'...';
                $text = $smallmessage ? $smallmessage : $fulltext;

                $msg = [
                    'text'   => strlen($text) > PREVIEW_MESSAGE_LENGH
                        ? substr($text, 0, PREVIEW_MESSAGE_LENGH).'...'
                        : $text,
                    'userid' => $msg->useridfrom,
                    'ufullname' => $msg->firstname. ' '. $msg->lastname,
                    'pic'    => $msg->picture
                        ? $this->renderer->user_picture(current($DB->get_records('user', ['id' => $msg->useridfrom])), [])
                        : ''
                ];
            }
            $response['messages'] = array_values($response['messages']);
        }

        return $response;
    }

    private function getBadges()
    {
        global $USER;
        
        $badges = badges_get_user_badges($USER->id, 0, 0, 0, '', false);

        $images = array_map(function($badge) {
            $context = \context_system::instance();
            $imageurl = \moodle_url::make_pluginfile_url(
                $context->id, 
                'badges', 
                'badgeimage', 
                $badge->id, 
                '/', 
                'f1', 
                false
            );
            return [
                'image' => $imageurl->__toString(),
                'name'  => $badge->name
            ];
        }, $badges);

        $images = array_values($images);

        return [
            'badges' => array_slice($images, 0, 8),
            'total'  => count($images)
        ];
    }

    private function getTotaraMenu()
    {
        global $CFG;
        $renderer = $this->renderer;

        $totaramenu = '';
         if (empty($renderer->page->layout_options['nocustommenu'])) {
            $menudata = totara_build_menu();
            $totara_core_renderer = $renderer->page->get_renderer('totara_core');
            $totaramenu = $totara_core_renderer->glztop_menu($menudata);
         }
         
        return $totaramenu;
    }
     
     private function getTotaraMenu123()
    {
        global $CFG, $PAGE;
        //error_reporting(E_ALL);
        //ini_set('display_errors', 1);
        $renderer = $this->renderer;
        //$PAGE->requires->js_call_amd('totara_core/totaramenu', null, true);
        
        $totaramenu = '';
         //if (empty($renderer->page->layout_options['nocustommenu'])) {
            $menudata = totara_build_menu();
            $totara_core_renderer = $renderer->page->get_renderer('totara_core');
            $totaramenu = $totara_core_renderer->mastheadglz(1);
        // }
         //print_r($totaramenu); die('1111111');
        return $totaramenu;
    }
    private function getLogo()
    {
        global $CFG;
        return $this->getSettingFile(HeaderSettings::LOGO_IMAGE)
            ? $this->getSettingFile(HeaderSettings::LOGO_IMAGE)
            : $CFG->wwwroot.'/theme/golearningzone/pix/default/logo.png';
    }  
}
