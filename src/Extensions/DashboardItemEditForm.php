<?php

namespace SilverStripeDashboard\Extensions;

use Extension;
use Injector;
use HiddenField;

class DashboardItemEditForm extends Extension
{


    public function updateItemEditForm($form) {
        if($id = $this->owner->request->getVar('ID')) {
            Injector::inst()->get("CMSMain")->setCurrentPageID($id);
            $form->Fields()->push(new HiddenField('ID','', $id));
        }
    }
}