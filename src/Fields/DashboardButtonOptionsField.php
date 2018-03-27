<?php

namespace SilverStripeDashboard\Fields;

use SilverStripe\Forms\OptionsetField;
use SilverStripe\View\Requirements;

/**
 * Class DashboardButtonOptionsField
 * @package SilverStripeDashboard\Fields
 */
class DashboardButtonOptionsField extends OptionsetField
{

    /**
     * @var
     */
    protected $Size;

    /**
     * @param array $attributes
     * @return \SilverStripe\ORM\FieldType\DBHTMLText
     */
    public function FieldHolder($attributes = array())
    {
//		Requirements::css("dashboard/css/dashboard-button-options.css");
        Requirements::javascript("dashboard/javascript/dashboard-button-options.js");

        return parent::FieldHolder($attributes);
    }

    /**
     * @param $size
     * @return $this
     */
    public function setSize($size)
    {
        $this->Size = $size;

        return $this;
    }
}