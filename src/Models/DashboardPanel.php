<?php

namespace SilverStripeDashboard\Models;

use SilverStripe\ORM\DataObject;
use SilverStripe\Core\Injector;
use SilverStripe\Core\Config\Config;
use SilverStripe\Control\Controller;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\TextField;
use SilverStripe\ORM\ArrayList;
use SilverStripe\Security\Member;
use SilverStripe\Security\Permission;
use SilverStripe\SiteConfig\SiteConfig;
use SilverStripeDashboard\Admin\Dashboard_PanelRequest;
use SilverStripeDashboard\Admin\Dashboard;
use SilverStripeDashboard\Fields\DashboardButtonOptionsField;

/**
 * Defines the DashboardPanel dataobject. All dashboard panels must descend from this class.
 *
 * @package dashboard
 * @author Uncle Cheese <unclecheese@leftandmain.com>
 */
class DashboardPanel extends DataObject
{


    /**
     * @var array
     */
    private static $db = array(
        'Title'     => 'Varchar(50)',
        'PanelSize' => "Enum('small,normal,large','normal')",
        'SortOrder' => 'Int'
    );


    /**
     * @var array
     */
    private static $has_one = array(
        'Member'     => Member::class,
        'SiteConfig' => SiteConfig::class
    );


    /**
     * @var string
     */
    private static $default_sort = "SortOrder ASC";


    /**
     * @var string The size of the dashboard panel. Options: "small", "normal", and "large"
     */
    private static $size = "normal";


    /**
     * @var string The path to the icon image that represents this dashboard panel type
     */
    private static $icon = "dashboard/images/dashboard-panel-default.png";


    /**
     * @var int The "weight" of the dashboard panel when listed in the available panels.
     *            Higher is lower in the list.
     */
    private static $priority = 100;


    /**
     * @var bool Show the configure form after creating. Used for panels that require
     * configuration in order to show data
     */
    private static $configure_on_create = false;


    /**
     * @var string The name of the template used for the contents of this panel.
     */
    protected $template;


    /**
     * @var string the name of the template used for the wrapper of this panel
     */
    protected $holderTemplate = DashboardPanel::class;


    /**
     * @var string The name of the request handler class that the Dashbaord controller
     * will use to communicate with a given panel
     */
    protected $requestHandlerClass = Dashboard_PanelRequest::class;


    /**
     * Allows the panel to be added
     *
     * @return string
     */
    public function registered()
    {
        return true;
    }


    /**
     * Gets the template, falls back on a default value of the class name
     *
     * @return string
     */
    protected function getTemplate()
    {
        return $this->template ? $this->template : $this->class;
    }


    /**
     * Gets the holder template
     *
     * @return string
     */
    public function getHolderTemplate()
    {
        return $this->holderTemplate;
    }


    /**
     * Gets the request handler class
     *
     * @return string
     */
    public function getRequestHandlerClass()
    {
        return $this->requestHandlerClass;
    }


    /**
     * Essentially an abstract method. Every panel must have this method defined to provide
     * a title to the panel selection window
     *
     * @return string
     */
    public function getLabel()
    {

    }


    /**
     * Essentially an abstract method. Every panel must have this method defined to provide
     * a description to the panel selection window
     *
     * @return string
     */
    public function getDescription()
    {

    }


    /**
     * An accessor to the Dashboard controller
     *
     * @return Dashboard
     */
    public function getDashboard()
    {
        return Injector::inst()->get(Dashboard::class);
    }


    /**
     * Renders the panel to its template
     *
     * @return SSViewer
     */
    public function render()
    {
        return $this->renderWith($this->holderTemplate);
    }


    /**
     * A template accessor for the icon of this panel
     *
     * @return string
     */
    public function Icon()
    {
        return Config::inst()->get($this->class, "icon", Config::INHERITED);
    }


    /**
     * Renders the inner contents of the panel. Similar to $Layout in pages.
     *
     * @return SSViewer
     */
    public function Content()
    {
        return $this->renderWith($this->getTemplate());
    }


    /**
     * The link to this panel through the Dashboard controller
     *
     * @return string
     */
    public function Link($action = null)
    {
        return Controller::join_links($this->getDashboard()->Link("panel/{$this->ID}"), $action);
    }


    /**
     * The link to delete this panel from the dashboard
     *
     * @return string
     */
    public function DeleteLink()
    {
        return $this->Link("delete");
    }


    /**
     * The link to create this panel on the dashboard
     *
     * @return string
     */
    public function CreateLink()
    {
        return Controller::join_links($this->getDashboard()->Link("panel/new"), "?type={$this->class}");
    }


    /**
     * Template accessor for the $configure_on_create boolean
     *
     * @return boolean
     */
    public function ShowConfigure()
    {
        return $this->stat('configure_on_create');
    }


    /**
     * Gets the {@link FieldList} object that is used to configure the fields on this panel.
     * Similar to getCMSFields().
     *
     * @return FieldList
     */
    public function getConfiguration()
    {
        return FieldList::create(DashboardButtonOptionsField::create("PanelSize", _t('Dashboard.PANELSIZE', ''), array(
            'small'  => '',
            'normal' => '',
            'large'  => ''
        ))->setSize("small"),

            TextField::create("Title", _t('Dashboard.TITLE', 'Title')));
    }


    /**
     * Gets the primary actions, which may appear in the top of the panel
     *
     * @return ArrayList
     */
    public function getPrimaryActions()
    {
        return ArrayList::create(array());
    }


    /**
     * Gets the secondary actions, which may appear in the bottom of the panel
     *
     * @return ArrayList
     */
    public function getSecondaryActions()
    {
        return ArrayList::create(array());
    }


    /**
     * Renders the entire panel. Similar to {@link FormField::FieldHolder()}
     *
     * @return SSViewer
     */
    public function PanelHolder()
    {
        return $this->renderWith($this->holderTemplate);
    }


    /**
     * For backward compatibility to the old static $size property.
     *
     * @return string
     */
    public function Size()
    {
        return $this->PanelSize;
    }


    /**
     * Gets the configuration form for this panel
     *
     * @return Form
     */
    public function Form()
    {
        return Dashboard_PanelRequest::create($this->getDashboard(), $this)->ConfigureForm();
    }


    /**
     * Duplicates this panel. Drills down into the has_many relations
     *
     * @return DashboardPanel
     */
    public function duplicate($doWrite = true, $relations = null)
    {
        $clone = parent::duplicate(true);
        foreach ($this->has_many() as $relationName => $relationClass) {
            foreach ($this->$relationName() as $relObject) {
                $relClone = $relObject->duplicate(false);
                $relClone->DashboardPanelID = $clone->ID;
                $relClone->write();
            }
        }

        return $clone;
    }


    /**
     * @param null $member
     * @param array $context
     * @return bool
     */
    public function canCreate($member = null, $context = array())
    {
        $m = $member ? $member : Member::currentUser();

        return Permission::check("CMS_ACCESS_DashboardAddPanels");
    }


    /**
     * @param null $member
     * @return bool
     */
    public function canDelete($member = null)
    {
        $m = $member ? $member : Member::currentUser();

        return Permission::check("CMS_ACCESS_DashboardDeletePanels") && $this->MemberID == $m->ID;
    }


    /**
     * @param null $member
     * @return bool
     */
    public function canEdit($member = null)
    {
        $m = $member ? $member : Member::currentUser();

        return Permission::check("CMS_ACCESS_DashboardConfigurePanels") && $this->MemberID == $m->ID;
    }


    /**
     * @param null $member
     * @return bool
     */
    public function canView($member = null)
    {
        $m = $member ? $member : Member::currentUser();

        return Permission::check("CMS_ACCESS_Dashboard") && $this->MemberID == $m->ID;
    }

    /**
     * @return bool
     */
    public function IsConfigured()
    {
        return true;
    }

}