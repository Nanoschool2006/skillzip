<?php

namespace NmBridgeWoocommerce;

use app\wisdmlabs\edwiserBridge as edwiserBridge;

if (!class_exists("EbWooIntSendEmailer")) {

    class EbWooIntSendEmailer
    {
        /**
         * This function send an course enrollment email on order completion
         * @param  [Array] $args Arguments array
         */
        public function sendCourseEnrollmentEmail($args)
        {
            $emailTmplData = edwiserBridge\EBAdminEmailTemplate::getEmailTmplContent("eb_emailtmpl_woocommerce_moodle_course_notifn");
            $allowNotify = get_option("eb_emailtmpl_woocommerce_moodle_course_notifn_notify_allow");
            if ($emailTmplData && $allowNotify == "ON") {
                $emailTmplObj = new edwiserBridge\EBAdminEmailTemplate();
                return $emailTmplObj->sendEmail($args['user_email'], $args, $emailTmplData);
            }
        }
    }
}
