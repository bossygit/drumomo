<?php

namespace Drupal\napay\Plugin\Commerce\CheckoutPane;

use Drupal\commerce_checkout\Plugin\Commerce\CheckoutPane\CheckoutPaneInterface;
use Drupal\commerce_checkout\Plugin\Commerce\CheckoutPane\CheckoutPaneBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\node\Entity\Node; 


/**
 * @CommerceCheckoutPane(
 *  id = "napay_checkout_pane",
 *  label = @Translation("Mobile money."),
 *  display_label = @Translation("Mobile money."),
 *  default_step = "string",
 *  wrapper_element = "string",
 * )
 */
class NapayCheckoutPane extends CheckoutPaneBase implements  CheckoutPaneInterface {
    
        protected $node_id;
        protected $total;
        protected $total_price;
        protected $order_number;
    




      /**
      * {@inheritdoc}
      */
      public function buildPaneForm(array $pane_form, FormStateInterface $form_state, array &$complete_form) {
        // Builds the pane form.
		
		$store = \Drupal::service('commerce_store.current_store')->getStore();
		
		$cart = \Drupal::service('commerce_cart.cart_provider')->getCart('default',$store);
		
		$this->total_price = $cart->getTotalPrice();
		
		$this->total = $this->total_price->getNumber();
		
		$help_text = "<p class='pinst'>1. <br/><img src='/sites/default/files/mobile-money.png' alt='mobile money'/> Pour MTN effectuez un transaction de $this->total_price au 06 560 45 45 par Mtn Mobile Money <span class='hond'><a href='tel:*105*2%23'>*105#</a></span>.</p>";
		$help_text .= "<p class='pinst'><img src='/sites/default/files/airtel-money.png' alt='airtel money'/> Pour Airtel effectuez un transaction de $this->total_price au 05 345 56 56 par Airtel Money <span class='hond'><a href='tel:*128*2%23'>*128#</a></span>.</p>";
		$help_text .= "<p class='pinst'>2. Attendez la confirmation par sms de la pharmacie</p>";
		$help_text .= "<p class='pinst'>3. Saisissez le numero de téléphone utilisé pour le transfert et Appuyez sur <b>Payer et terminer votre achat</b></p>";
		
		$attr = array("placeholder" => "Saisissez votre numéro de téléphone");
		
		$current_path = \Drupal::service('path.current')->getPath();
		
		\Drupal::logger('napay')->notice("Le chemin : ".$current_path);
		$path = explode("/",$current_path );
		$this->order_number = (int)$path[2];
		
		\Drupal::logger('napay')->notice("Order number : ".$this->order_number);
		

		

        $pane_form['mobile_number'] = array(
          '#type' => 'textfield',
          '#title' => $help_text,
          '#size' => '10',
          '#attributes' => $attr,
          '#required' => TRUE,
          );
        return $pane_form;
      }
      /**
      * {@inheritdoc}
      */
      public function validatePaneForm(array &$pane_form, FormStateInterface $form_state, array &$complete_form) {
        // Validates the pane form.
		
	    	$values = $form_state->getValue($pane_form['#parents']);
	    	$prix = explode(" ",$this->total_price);
            $talo = (int) $prix[0];
		
			if (!preg_match("#^0[456]{1}[0-9]{7}$#", $values['mobile_number']))
			{
			    \Drupal::logger('napay')->notice("Regex verification");
				$form_state->setError($pane_form,t("<p class='bg-danger text-white p-3 rounded-pill'>Entrez un numéro Airtel ou Mtn valide</p>"));
			}
			
			else if(!$this->ifPending($values['mobile_number'])){
			    \Drupal::logger('napay')->notice("No transfert");
			    
             $form_state->setError($pane_form,t("<p class='bg-danger text-white p-3 rounded-pill'>Vous n'avez pas encore effectué de transfert</p>"));
             
             }
             
            else if($this->ifInsufficient($values['mobile_number']) AND ($this->ifPending($values['mobile_number']))){
                \Drupal::logger('napay')->notice("Adding to balance");
		    
		    $nodepending = Node::load($this->pendingNodeId($values['mobile_number']));
		    $nodeinsufficient = Node::load($this->insufficientNodeId($values['mobile_number']));
		   
		    $montant = $nodepending->get('field_amount')->getValue();
            $balance = $nodeinsufficient->get('field_amount')->getValue();
            
            
            
            $somme = (int)$montant[0]['value'] + (int)$balance[0]['value'];
            $recu = $montant[0]['value'];
            
            \Drupal::logger('napay')->notice("Montant recu ".$montant[0]['value']." + ".(int)$balance[0]['value']." = ".$somme );
            
            \Drupal::logger('napay')->notice("Talo : ".$talo);
            
            
            
                    
            
            if($somme < $talo ) {
                
                \Drupal::logger('napay')->notice("somme : ".$somme);
                
            $balance = $talo - $somme;
            $nodepending->field_balance->value = $balance;
            $nodepending->field_amount->value = $somme;
            $nodepending->field_status->value = "3";
            $nodepending->save();
            $nodeinsufficient->field_status->value = "2";
            $nodeinsufficient->save();
                
                
                
                $form_state->setError($pane_form,t("<p class='bg-warning text-white p-3 rounded-pill'>Vous avez envoyé $recu FCFA. Transferez $balance FCFA pour completer la balance</p>"));
                
                
            }
            
          
		    

		}
             
             else if($this->ifPending($values['mobile_number'])){
                 \Drupal::logger('napay')->notice("Last bloc ");
            
                 
             $nodepending = Node::load($this->pendingNodeId($values['mobile_number']));
             $montant = $nodepending->get('field_amount')->getValue();
             $recu = $montant[0]['value'];
             
             if((int)$montant[0]['value'] < $talo ) {
                 $balance = $talo - (int)$montant[0]['value'];
            $nodepending->field_balance->value = $balance;
            $nodepending->field_status->value = "3";
            $nodepending->save();
                 
                  $form_state->setError($pane_form,t("Vous avez transferez $recu FCFA. Transferez $balance FCFA pour completer la balance"));
                 
             }
			    
             
             
             }
       
   
		

		

      }
      /**
      * {@inheritdoc}
      */
      public function submitPaneForm(array &$pane_form, FormStateInterface $form_state, array &$complete_form) {
        // Handles the submission of an pane form.
        $values = $form_state->getValue($pane_form['#parents']);
        $nodepending = Node::load($this->pendingNodeId($values['mobile_number']));
         $nodepending->field_status->value = "2";
        $nodepending->save();
        /*
        $order = \Drupal\commerce_order\Entity\Order::load($this->order_number);
        $payments = \Drupal\commerce_order\Payment\Payment::loadByProperties(['order_id' => $order->id()]);
        
        foreach ($payments as $payment) {
        $payment->setState('completed')->save();
                }
        */

        \Drupal::logger('napay')->notice("SubmitForm Complete order todo");
      }
      
      public function ifInsufficient($numero){
          		

	$query = \Drupal::entityQuery('node');
	$query->condition('type', 'notification');
	$query->condition('field_telephone_number',$numero, '=');
	$query->condition('field_status',"3", '=');
	$query->range(0, 1);
	$nb_resultats = $query->count()->execute();
	return (bool)$nb_resultats;
      }
      
      public function insufficientNodeId($numero){
          		

	$query = \Drupal::entityQuery('node');
	$query->condition('type', 'notification');
	$query->condition('field_telephone_number',$numero, '=');
	$query->condition('field_status',"3", '=');
	$query->range(0, 1);
	$resultats = $query->execute();
	   foreach ($resultats as $nids) {

	    	\Drupal::logger('napay')->notice("Node insufficient: ".$nids);
	    	$nodeid = $nids;
       
   }
   return $nodeid;
      }
      
      public function ifPending($numero){
          		

	$query = \Drupal::entityQuery('node');
	$query->condition('type', 'notification');
	$query->condition('field_telephone_number',$numero, '=');
	$query->condition('field_status',"1", '=');
	$query->range(0, 1);
	$nb_resultats = $query->count()->execute();
	return (bool)$nb_resultats;
      }
      
      public function pendingNodeId($numero){
          		

	$query = \Drupal::entityQuery('node');
	$query->condition('type', 'notification');
	$query->condition('field_telephone_number',$numero, '=');
	$query->condition('field_status',"1", '=');
	$query->range(0, 1);
	$resultats = $query->execute();
	   foreach ($resultats as $nids) {

	    	\Drupal::logger('napay')->notice("Node pending :".$nids);
	    	$nodeid = $nids;
       
   }
   return $nodeid;
      }
      
      


}
