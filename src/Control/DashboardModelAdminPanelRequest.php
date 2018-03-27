<?php


namespace SilverStripeDashboard\Control;

use SilverStripe\Control\HTTPRequest;
use SilverStripe\Core\Convert;


/**
 * This custom request handler allows controller actions that are unique to this panel type
 *
 * @author UncleCheese <unclecheese@leftandmain.com>
 * @package Dashbaord
 */
class DashboardModelAdminPanelRequest extends DashboardPanelRequest
{

    private static $allowed_actions = array(
        "modelsforpanel"
    );

    /**
     * Given a requested ModelAdmin subclass, get the managed models and provide a JSON response
     *
     * @param HTTPRequest $r
     * @return string
     */
    public function modelsforpanel(HTTPRequest $r)
    {
        $panel = $r->requestVar('modeladminpanel');

        return Convert::array2json($this->panel->getManagedModelsFor($panel));
    }


}