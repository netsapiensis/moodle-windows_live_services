<?php

class admin_setting_html extends admin_setting {

    public $html;

    private $file;

    /**
     * Config text constructor
     *
     * @param string $name unique ascii name, either 'mysetting' for settings that in config, or 'myplugin/mysetting' for ones in config_plugins.
     * @param string $visiblename localised
     * @param string $description long localised info
     * @param string $defaultsetting
     * @param string $file
     */
    public function __construct($name, $visiblename, $description, $defaultsetting, $file) {
        
        $this->file = $file;
        $name = 'block_course_menu_' . $name;
        parent::__construct($name, $visiblename, $description, $defaultsetting);
    }

    /**
     * Return the setting
     *
     * @return mixed returns config if successful else null
     */
    public function get_setting() {
        return unserialize($this->config_read($this->name));
    }

    public function write_setting($data) {
        
        return true;
    }

    /**
     * Return an XHTML string for the setting
     * @return string Returns an XHTML string
     */
    public function output_html($data, $query = '') {

        ob_start();
        
        if (is_file(dirname(__FILE__) . '/../config/' . $this->file)) {
            require_once dirname(__FILE__) . '/../config/' . $this->file;
        }
        
        $this->html = ob_get_clean();
        
        if (!$this->html)
            $this->html = $this->file;
        
        return $this->html;
    }
}
?>