<?php
if (!defined('ABSPATH')) {
    exit; // Silence is golden!
}

class TD_Plugin_Installer_Skin extends \Plugin_Installer_Skin
{
    public $done_header = true;
    public $done_footer = true;
    public $messages = [];

    /**
     * Request filesystem credentials.
     *
     * @param bool   $error                      Whether the request is due to an authentication error.
     * @param bool   $context                    The context for the request.
     * @param bool   $allow_relaxed_file_ownership Whether to allow relaxed file ownership.
     *
     * @return array The filesystem credentials.
     */
    public function request_filesystem_credentials($error = false, $context = false, $allow_relaxed_file_ownership = false)
    {
        return $this->options;
    }

    /**
     * Provide feedback.
     *
     * @param string $string The feedback message.
     * @param mixed  ...$args Additional arguments.
     *
     * @return void
     */
    public function feedback($string, ...$args)
    {
        if (empty($string)) {
            return;
        }
        $this->messages[] = $string;
    }
}
