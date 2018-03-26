<?php

namespace SilverStripeDashboard\Models;

use DataObject;
use FieldList;
use Form;
use Injector;
use SilverStripeDashboard\Admin\Dashboard;


/** 
 * A {@link DataObject} subclass that is required for use on a has_many relationship
 * on a DashboardPanel when being managed with a {@link DashboardHasManyRelationEditor}
 *
 * @package Dashboard
 * @author Uncle Cheese <unclecheese@leftandmain.com>
 */
class DashboardPanelDataObject extends DataObject {



	private static $db = array (
		'SortOrder' => 'Int'
	);



	private static $has_one = array (
		'DashboardPanel' => DashboardPanel::class
	);


	private static $default_sort = "SortOrder ASC";

	

	/**
	 * @var string Like $summary_fields, but these objects only render one field in list view.
	 */
	private static $label_field = "ID";




	public function getConfiguration() {
		$fields = FieldList::create();	
		return $fields;
	}




	/**
	 * Gets a form for editing or creating this object
	 *
	 * @return Form
	 */
	public function getConfigFields() {
		$form = Form::create(Injector::inst()->get(Dashboard::class), "Form", $this->getConfiguration());
	}



}