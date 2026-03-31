<?php
defined('ABSPATH') or die("you do not have access to this page!");

if (!class_exists("cmplz_comments")) {
    class cmplz_comments
    {
        private static $_this;

        function __construct()
        {
            if (isset(self::$_this))
	            wp_die(sprintf('%s is a singleton class and you cannot create a second instance.', get_class($this)));

            self::$_this = $this;


        }

        static function this()
        {
            return self::$_this;
        }

        public function site_uses_comments()
        {
            $post_types = get_post_types();
            foreach($post_types as $post_type){
                $args = array(
                    'posts_per_page' => 5,
                    'post_type' => $post_type,
                );
                $posts = get_posts($args);
                foreach ($posts as $post){
                    if (comments_open($post->ID)) return true;
                }
            }

            return false;
        }


    }
} //class closure
