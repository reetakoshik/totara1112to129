<?php

namespace GoLearningZone\Traits;

require_once __DIR__.'/../Pages.php';
require_once __DIR__.'/../Partials.php';
require_once __DIR__.'/../Pages.php';
require_once __DIR__.'/../Partials.php';
require_once __DIR__.'/../Settings.php';

require_once __DIR__.'/../../../../user/lib.php';

use GoLearningZone\Pages\Front as FrontPage;
use GoLearningZone\Pages\Admin as AdminPage;
use GoLearningZone\Pages\Login as LoginPage;
use GoLearningZone\Pages\Report as ReportPage;
use GoLearningZone\Pages\Noblocks as NoblocksPage;
use GoLearningZone\Pages\Dashboard as DashboardPage;
use GoLearningZone\Pages\Course as CoursePage;
use GoLearningZone\Pages\DefaultPage as DefaultPage;
use GoLearningZone\Pages\DefaultgoalPage as DefaultgoalPage;
use GoLearningZone\Pages\EmptyPage as EmptyPage;
use GoLearningZone\Pages\MyPublic as MyPublicPage;
use GoLearningZone\Pages\Popup as PopupPage;
use GoLearningZone\Partials\Header as Header;
use GoLearningZone\Partials\Footer as Footer;

trait Renderer
{
	static $format_renderer;

    public function render_admin()
    {
        $page = new AdminPage($this);
        $html = $page->render();
        return $html;
    }

    public function render_frontpage()
    {
        $page = new FrontPage($this);
        $html = $page->render();
        return $html;
    }

    public function render_default()
    {
        $page = new DefaultPage($this);
        $html = $page->render();
        return $html;
    }
	
	public function render_defaultgoal()
    {
        $page = new DefaultgoalPage($this);
        $html = $page->render();
        return $html;
    }

    public function render_loginpage()
    {   
        $page = new LoginPage($this);
        $html = $page->render();
        return $html;
    }

    public function render_report()
    {
        $page = new ReportPage($this);
        $html = $page->render();
        return $html;
    }

    public function render_noblocks()
    {
        $page = new NoblocksPage($this);
        $html = $page->render();
        return $html;
    }

    public function render_dashboard()
    {
        $page = new DashboardPage($this);
        $html = $page->render();
        return $html;
    }

    public function render_course($course)
    {       
        $page = new CoursePage($this, self::$format_renderer, $course);
        $html = $page->render();
        return $html;
    }

    public function render_empty()
    {       
        $page = new EmptyPage($this);
        $html = $page->render();
        return $html;
    }

    public function render_mypublic()
    {       
        $page = new MyPublicPage($this);
        $html = $page->render();
        return $html;
    }

    public function render_popup()
    {
        $page = new PopupPage($this);
        $html = $page->render();
        return $html;
    }

    public function render_header()
    {
        $header = new Header($this);
        $html = $header->render();
        return $html;
    }

    public function render_footer()
    {
        $footer = new Footer($this);
        $html = $footer->render();
        return $html;
    }

    public function set_format_renderer($renderer)
    {
        // for some reason moodle create multiple instances of this renderer
        self::$format_renderer = $renderer;
    }

    public function __get($name) 
    { 
        return $this->$name;
    }
}