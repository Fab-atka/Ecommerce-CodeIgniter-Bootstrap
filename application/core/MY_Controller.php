<?php

class MY_Controller extends MX_Controller {

    public $my_lang;
	public $my_lang_full;
    public $def_lang;
    public $lang_link;
    public $all_langs;
	private $sum_values = 0;
	public $currency;
    
    public function __construct() {
        parent::__construct();
        $this->load->model('admin/Admin_model');
        $this->setLanguage();
    }

    public function render($view, $head, $data = null, $footer = null) {
		$head['cartItems'] = $this->getCartItems();
		$head['sumOfItems'] = $this->sum_values;
    	$vars['lang_url'] = base_url($this->lang_link.'/');
		$vars['currency'] = $this->currency;
    	$this->load->vars($vars); 
        $this->load->view('_parts/header', $head);
        $this->load->view($view, $data);
        $this->load->view('_parts/footer', $footer);
    }
	
	public function getCartItems() {
		if((!isset($_SESSION['shopping_cart']) || empty($_SESSION['shopping_cart'])) && get_cookie('shopping_cart') != NULL) {
			$_SESSION['shopping_cart'] = unserialize(get_cookie('shopping_cart'));
		} elseif(!isset($_SESSION['shopping_cart']) || empty($_SESSION['shopping_cart'])) {
			return 0;
		}
		$result['array'] = $this->Articles_model->getShopItems(array_unique($_SESSION['shopping_cart']), $this->my_lang);
		if(empty($result)) {
			unset($_SESSION['shopping_cart']);
			@delete_cookie('shopping_cart');
			return 0;
		}
		$count_articles = array_count_values($_SESSION['shopping_cart']);
		$this->sum_values = array_sum($count_articles);
		$finalSum = 0;
		foreach($result['array'] as &$article) {
			$article['num_added'] = $count_articles[$article['id']];
			$article['sum_price'] = $article['price'] * $count_articles[$article['id']];
			$finalSum = $finalSum + $article['sum_price'];
			$article['sum_price'] = number_format($article['sum_price'], 2);
			$article['price'] = $article['price']!= '' ? number_format($article['price'], 2):0;
		}
		$result['finalSum'] = number_format($finalSum, 2);
		return $result;
	}

    private function setLanguage() { //set language of site
        $langs = $this->Admin_model->getLanguages();
        $have = 0;
        $def_lang = $this->config->item('language');
        $def_lang_abbr = $this->def_lang = $this->config->item('language_abbr');
		$this->currency = $this->config->item('currency');
        if ($this->uri->segment(1) == $def_lang_abbr) {
			redirect(base_url());  
        }
        foreach ($langs->result() as $lang) {
        	$this->all_langs[$lang->abbr]['name']=$lang->name;
        	$this->all_langs[$lang->abbr]['flag']=$lang->flag;
            if ($lang->abbr == $this->uri->segment(1)) {
                $this->session->set_userdata('lang', $lang->name);
                $this->session->set_userdata('lang_abbr', $lang->abbr);
				$this->currency = $lang->currency;
                $have = 1;
            }
        }
        if ($have == 0)
            $this->session->unset_userdata('lang');

        if ($this->session->userdata('lang') !== NULL) {
            $this->lang->load("site", $this->session->userdata('lang'));
        } else {
            $this->session->set_userdata('lang', $def_lang);
            $this->session->set_userdata('lang_abbr', $def_lang_abbr);
            $this->lang->load("site", $def_lang);
        }
        $this->my_lang = $this->session->userdata('lang_abbr');
		$this->my_lang_full = $this->session->userdata('lang');

        $this->my_lang != $this->def_lang ? $this->lang_link = $this->my_lang . '/' : $this->lang_link = '';
    }
	
	public function clearShoppingCart() {
		unset($_SESSION['shopping_cart']);
		@delete_cookie('shopping_cart');
		if ($this->input->is_ajax_request()) {
			echo 1;
		}
	}

}
