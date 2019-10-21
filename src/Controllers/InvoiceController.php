<?php

namespace PortalCMS\Controllers;

use PortalCMS\Core\Controllers\Controller;
use PortalCMS\Core\HTTP\Redirect;
use PortalCMS\Core\HTTP\Request;
use PortalCMS\Modules\Invoices\InvoiceModel;
use PortalCMS\Models\InvoiceItem;

/**
 * InvoiceController
 * Controls everything that is invoice-related
 */
class InvoiceController extends Controller
{
    public function __construct()
    {
        parent::__construct();

        if (isset($_POST['createInvoiceMail'])) {
            Invoice::createMail();
        }
        if (isset($_POST['writeInvoice'])) {
            $id = Request::post('id', true);
            if (!Invoice::write($id)) {
                Redirect::error();
            }
        }
        if (isset($_POST['createInvoice'])) {
            Invoice::create();
        }
        if (isset($_POST['deleteInvoice'])) {
            Invoice::delete();
        }
        if (isset($_POST['deleteInvoiceItem'])) {
            InvoiceItem::delete();
        }
        if (isset($_POST['addInvoiceItem'])) {
            InvoiceItem::create();
        }
    }
}
