<?php



class ModelExtensionPaymentVandar extends Model
{
    public function getMethod($address)
    {
        $this->load->language('extension/payment/vandar');

        if ($this->config->get('payment_vandar_status')) {

            $status = true;

        } else {

            $status = false;
        }

        $method_data = [];

        if ($status) {

            $method_data = [
                 'code'       => 'vandar',
                 'title'      => $this->language->get('درگاه پرداخت وندار'),
                 'terms'      => '',
                 'sort_order' => $this->config->get('payment_vandar_sort_order'),
            ];
        }

        return $method_data;
    }
}