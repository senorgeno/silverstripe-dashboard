<?php

namespace SilverStripeDashboard\Admin;

use SilverStripe\Admin\LeftAndMain;
use SilverStripe\ORM\DataObject;
use SilverStripe\Security\PermissionProvider;
use SilverStripe\View\Requirements;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Security\Member;
use SilverStripe\SiteConfig\SiteConfig;
use SilverStripe\Control\HTTPResponse;
use SilverStripe\Security\Permission;
use SilverStripe\ORM\ArrayList;
use SilverStripe\Core\Manifest\ClassLoader;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Core\Config\Config;
use SilverStripe\Control\RequestHandler;
use SilverStripe\Forms\Form;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\FormAction;
use SilverStripeDashboard\Models\DashboardPanel;

/** 
 * Defines the Dashboard interface for the CMS
 *
 * @package Dashboard
 * @author Uncle Cheese <unclecheese@leftandmain.com>
 */
class Dashboard extends LeftAndMain implements PermissionProvider {

	

	private static $menu_title = 'Dashboard';


	
	private static $url_segment = "dashboard";


	
	private static $menu_priority = 100;


	
	private static $url_priority = 30;

	
	
	private static $menu_icon = "unclecheese/dashboard: client/dist/images/dashboard.png";
	
	
	
	private static $tree_class = DashboardPanel::class;


	
	private static $url_handlers = array (
		
		'panel/$ID' => 'handlePanel',
		'$Action!' => '$Action',
		'' => 'index'
	);

	public function init() {
		parent::init();
		Requirements::css("unclecheese/dashboard: client/dist/styles/dashboard.css");
		Requirements::javascript("unclecheese/dashboard: client/dist/js/jquery.flip.js");
		Requirements::javascript("unclecheese/dashboard: client/dist/js/dashboard.js");
	}

	private static $allowed_actions = array(
		"handlePanel",
		"sort",
		"setdefault",
		"applytoall"
	);

	
	/**
	 * Provides custom permissions to the Security section
	 *
	 * @return array
	 */
	public function providePermissions() {
		$title = _t("Dashboard.MENUTITLE", LeftAndMain::menu_title_for_class(Dashboard::class));
		return array(
			"CMS_ACCESS_Dashboard" => array(
				'name' => _t('Dashboard.ACCESS', "Access to '{title}' section", array('title' => $title)),
				'category' => _t('Permission.CMS_ACCESS_CATEGORY', 'CMS Access'),
				'help' => _t(
					'Dashboard.ACCESS_HELP',
					'Allow use of the CMS Dashboard'
				)				
			),
			"CMS_ACCESS_DashboardAddPanels" => array(
				'name' => _t('Dashboard.ADDPANELS', "Add dashboard panels"),
				'category' => _t('Permission.CMS_ACCESS_CATEGORY', 'CMS Access'),
				'help' => _t(
					'Dashboard.ACCESS_HELP',
					'Allow user to add panels to his/her dashboard'
				)
			),
			"CMS_ACCESS_DashboardConfigurePanels" => array(
				'name' => _t('Dashboard.CONFIGUREANELS', "Configure dashboard panels"),
				'category' => _t('Permission.CMS_ACCESS_CATEGORY', 'CMS Access'),
				'help' => _t(
					'Dashboard.ACCESS_HELP',
					'Allow user to configure his/her dashbaord panels'
				),
			),
			"CMS_ACCESS_DashboardDeletePanels" => array(
				'name' => _t('Dashboard.DELETEPANELS', "Remove dashboard panels"),
				'category' => _t('Permission.CMS_ACCESS_CATEGORY', 'CMS Access'),
				'help' => _t(
					'Dashboard.ACCESS_HELP',
					'Allow user to remove panels from his/her dashbaord'
				)
			)
		);
	}


	

	/** 
	 * Handles a request for a {@link DashboardPanel} object. Can be a new record or existing
     *
     * @param HTTPRequest $r
     * @throws \SilverStripe\Control\HTTPResponse_Exception
     */
	public function handlePanel(HTTPRequest $r) {
		if($r->param('ID') == "new") {
			$class = $r->getVar('type');
			if($class && class_exists($class) && is_subclass_of($class, DashboardPanel::class)) {
				$panel = new $class();
				if($panel->canCreate()) {
					$panel->MemberID = Member::currentUserID();
					$panel->Title = $panel->getLabel();
					$panel->write();
				}
				else {
					$panel = null;
				}
			}
		}
		else {
			$panel = DashboardPanel::get()->byID((int) $r->param('ID'));
		}
		if($panel && ($panel->canEdit() || $panel->canView())) {
			$requestClass = $panel->getRequestHandlerClass();
			$handler = Injector::inst()->create($requestClass, $this, $panel);
			return $handler->handleRequest($r, DataObject::create()); // todo ss4 upgrade was set to DataModel...

		}
		return $this->httpError(404);
	}


    /**
     * A controller action that handles the reordering of the panels
     *
     * @param HTTPRequest $r
     * @return void
     * @throws \SilverStripe\ORM\ValidationException
     */
	public function sort(HTTPRequest $r) {
		if($sort = $r->requestVar('dashboard-panel')) {
			foreach($sort as $index => $id) {
				if($panel = DashboardPanel::get()->byID((int) $id)) {
					if($panel->MemberID == Member::currentUserID()) {
						$panel->SortOrder = $index;
						$panel->write();
					}					
				}				
			}
		}
	}


    /**
     * A controller action that handles setting the default dashboard configuration
     *
     * @param HTTPRequest $r
     * @return HTTPResponse
     */
	public function setdefault(HTTPRequest $r) {
		foreach(SiteConfig::current_site_config()->DashboardPanels() as $panel) {
			$panel->delete();
		}
		foreach(Member::currentUser()->DashboardPanels() as $panel) {
			$clone = $panel->duplicate();
			$clone->MemberID = 0;
			$clone->SiteConfigID = SiteConfig::current_site_config()->ID;
			$clone->write();
		}
		return new HTTPResponse(_t('Dashboard.SETASDEFAULTSUCCESS','Success! This dashboard configuration has been set as the default for all new members.'));
	}


    /**
     * A controller action that handles the application of a dashboard configuration to all members
     *
     * @param HTTPResponse $r
     * @return SS_HTTPResponse
     */
	public function applytoall(HTTPResponse $r) {
		$members = Permission::get_members_by_permission("CMS_ACCESS_Dashboard");
		foreach($members as $member) {
			if($member->ID == Member::currentUserID()) continue;
			
			$member->DashboardPanels()->removeAll();
			foreach(Member::currentUser()->DashboardPanels() as $panel) {
				$clone = $panel->duplicate();					
				$clone->MemberID = $member->ID;
				$clone->write();
			}			
		}
		return new SS_HTTPResponse(_t('Dashboard.APPLYTOALLSUCCESS','Success! This dashboard configuration has been applied to all members who have dashboard access.'));
	}




	/**
	 * Gets the current user's dashboard configuration
	 *
	 * @return DataList
	 */
	public function BasePanels() {
		return Member::currentUser()->DashboardPanels();
	}
	
	/**
	 * Gets the current user's dashboard configuration
	 *
	 * @return DataList
	 */
	public function Panels() {
		return Member::currentUser()->DashboardPanels();
	}




	/**
	 * Gets all the available panels that can be installed on the dashboard. All subclasses of
	 * {@link DashboardPanel} are included
	 *
	 * @return ArrayList
	 */
	public function AllPanels() {
		$set = ArrayList::create(array());
		$panels = ClassLoader::inst()->getManifest()->getDescendantsOf(DashboardPanel::class);
		if($this->config()->excluded_panels) {
			$panels = array_diff($panels,$this->config()->excluded_panels);
		}
		foreach($panels as $class) {
			$SNG = Injector::inst()->get($class);
			$SNG->Priority = Config::inst()->get($class, "priority", Config::INHERITED);
			if($SNG->registered() == true){
				$set->push($SNG);
			}
		}
		return $set->sort("Priority");
	}




	/**
	 * A template accessor to check the ADMIN permission
	 *
	 * @return bool
	 */
	public function IsAdmin() {
		return Permission::check("ADMIN");
	}



	/**
	 * Check the permission to make sure the current user has a dashboard
	 *
	 * @return bool
	 */
	public function canView($member = null) {
		return Permission::check("CMS_ACCESS_Dashboard");
	}



	/** 
	 * Check if the current user can add panels to the dashboard
	 *
	 * @return bool
	 */
	public function CanAddPanels() {
		return Permission::check("CMS_ACCESS_DashboardAddPanels");
	}



	/** 
	 * Check if the current user can delete panels from the dashboard
	 *
	 * @return bool
	 */
	public function CanDeletePanels() {
		return Permission::check("CMS_ACCESS_DashboardDeletePanels");
	}



	/** 
	 * Check if the current user can configure panels on the dashboard
	 *
	 * @return bool
	 */
	public function CanConfigurePanels() {
		return Permission::check("CMS_ACCESS_DashboardConfigurePanels");
	}




}



