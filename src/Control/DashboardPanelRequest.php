<?php


namespace SilverStripeDashboard\Control;

use SilverStripe\Control\HTTPRequest;
use SilverStripe\Control\HTTPResponse;
use SilverStripe\Control\RequestHandler;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\Form;
use SilverStripe\Forms\FormAction;
use SilverStripe\View\SSViewer;
use SilverStripeDashboard\Admin\Dashboard;
use SilverStripeDashboard\Models\DashboardPanel;


/**
 * Defines the {@link RequestHandler} object that is responsible for rendering dashboard panels
 * and processing their input.
 *
 * @package Dashboard
 * @author Uncle Cheese <unclecheese@leftandmain.com>
 */
class DashboardPanelRequest extends RequestHandler
{


    /**
     * @var array
     */
    private static $url_handlers = array(
        '$Action!' => '$Action',
        ''         => 'panel'

    );

    /**
     * @var array
     */
    private static $allowed_actions = array(
        "panel",
        "delete",
        "ConfigureForm",
        "saveConfiguration"
    );


    /**
     * @var Dashboard
     */
    protected $dashboard;


    /**
     * @var DashboardPanel
     */
    protected $panel;


    /**
     * DashboardPanelRequest constructor.
     * @param Dashboard $dashboard
     * @param DashboardPanel $panel
     */
    public function __construct(Dashboard $dashboard, DashboardPanel $panel)
    {
        $this->dashboard = $dashboard;
        $this->panel = $panel;
        parent::__construct();
    }


    /**
     * Gets the link to this request. Useful for rendering the nested Form. Also provides an easy
     * "refresh" link to the panel that is managed by this request
     *
     * @return string
     */
    public function Link()
    {
        return $this->dashboard->Link("panel/{$this->panel->ID}");
    }


    /**
     * Renders the panel in this request
     *
     * @param HTTPRequest $r
     * @return SSViewer
     * @throws \SilverStripe\Control\HTTPResponse_Exception
     */
    public function panel(HTTPRequest $r)
    {
        if ($this->panel->canView()) {
            return $this->panel->PanelHolder();
        }

        return $this->httpError(403);
    }


    /**
     * Deletes the panel in this request
     *
     * @param HTTPRequest $r
     * @return HTTPResponse
     */
    public function delete(HTTPRequest $r)
    {
        if ($this->panel->canDelete()) {
            $this->panel->delete();

            return new HTTPResponse("OK");
        }
    }


    /**
     * Gets the configuration form for this panel and handles the form input
     *
     * @return Form
     */
    public function ConfigureForm()
    {
        $form = Form::create($this, "ConfigureForm", $this->panel->getConfiguration(),
            FieldList::create(FormAction::create("saveConfiguration", _t('Dashboard.SAVE', 'Save'))
                                        ->setUseButtonTag(true)->addExtraClass('ss-ui-action-constructive'),
                FormAction::create("cancel", _t('Dashboard.CANCEL', 'Cancel'))->setUseButtonTag(true)));
        $form->loadDataFrom($this->panel);
        $form->setHTMLID("Form_ConfigureForm_" . $this->panel->ID);
        $form->addExtraClass("configure-form");

        return $form;
    }


    /**
     * Processes the form input and writes the panel
     *
     * @param $data
     * @param $form
     * @return void
     * @throws \SilverStripe\ORM\ValidationException
     */
    public function saveConfiguration($data, $form)
    {
        $panel = $this->panel;
        $form->saveInto($panel);
        $panel->write();
    }


}