<?php

namespace SilverStripeDashboard\Models;

use SilverStripe\ORM\DataObject;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\Form;
use SilverStripe\Core\Injector\Injector;
use SilverStripeDashboard\Admin\Dashboard;


/**
 * A {@link DataObject} subclass that is required for use on a has_many relationship
 * on a DashboardPanel when being managed with a {@link DashboardHasManyRelationEditor}
 *
 * @package Dashboard
 * @author Uncle Cheese <unclecheese@leftandmain.com>
 */
class DashboardPanelDataObject extends DataObject
{


    /**
     * @var array
     */
    private static $db = array(
        'SortOrder' => 'Int'
    );


    /**
     * @var array
     */
    private static $has_one = array(
        'DashboardPanel' => DashboardPanel::class
    );


    /**
     * @var string
     */
    private static $default_sort = "SortOrder ASC";


    /**
     * @var string Like $summary_fields, but these objects only render one field in list view.
     */
    private static $label_field = "ID";


    /**
     * @return static
     */
    public function getConfiguration()
    {
        $fields = FieldList::create();

        return $fields;
    }


    /**
     * Gets a form for editing or creating this object
     *
     * @return void
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function getConfigFields()
    {
        $form = Form::create(Injector::inst()->get(Dashboard::class), "Form", $this->getConfiguration());
    }


}