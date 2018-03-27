<?php

namespace SilverStripeDashboard\Extensions;

use SilverStripe\ORM\DataExtension;
use SilverStripe\Forms\FieldList;
use SilverStripe\SiteConfig\SiteConfig;
use SilverStripe\ORM\DB;
use SilverStripeDashboard\Models\DashboardPanel;


/**
 * Decorates the Member object to work with the Dashboard interface
 *
 * @package Dashboard
 * @author Uncle Cheese <unclecheese@leftandmain.com>
 */
class DashboardMember extends DataExtension
{


    /**
     * @var array
     */
    private static $db = array(
        'HasConfiguredDashboard' => 'Boolean'
    );


    /**
     * @var array
     */
    private static $has_many = array(
        'DashboardPanels' => DashboardPanel::class
    );


    /**
     * Removes the DashboardPanels tab from the Security section. Panels should not be managed there.
     */
    public function updateCMSFields(FieldList $fields)
    {
        $fields->removeByName("DashboardPanels");
    }


    /**
     * Ensures that new members get the default dashboard configuration. Once it has been applied,
     * make sure this doesn't happen again, if for some reason a user insists on having an empty
     * dashboard.
     */
    public function onAfterWrite()
    {
        if (!$this->owner->HasConfiguredDashboard && !$this->owner->DashboardPanels()->exists()) {
            foreach (SiteConfig::current_site_config()->DashboardPanels() as $p) {
                $clone = $p->duplicate();
                $clone->SiteConfigID = 0;
                $clone->MemberID = $this->owner->ID;
                $clone->write();
            }

            DB::query("UPDATE Member SET HasConfiguredDashboard = 1 WHERE ID = {$this->owner->ID}");
            $this->owner->flushCache();
        }
    }
}