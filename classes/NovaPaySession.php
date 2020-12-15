<?php
/**
 * License
 * @author mnemonic88uk
 * @copyright 2020 mnemonic88uk
 * @license https://opensource.org/licenses/AFL-3.0 Academic Free License 3.0 (AFL-3.0)
 */

class NovaPaySession extends ObjectModel implements NovaPay\DataSourceInterface
{
    /**
     * @var string|null
     */
    public $session_id = null;

    /**
     * @var string|null
     */
    public $merchant_id = null;

    /**
     * @var string
     */
    public $client_first_name = '';

    /**
     * @var string
     */
    public $client_last_name = '';

    /**
     * @var string
     */
    public $client_patronymic = '';

    /**
     * @var string|null
     */
    public $client_phone = null;
    
    /**
     * @var string
     */
    public $callback_url = '';

    /**
     * @var string JSON
     */
    public $metadata = '';

    /**
     * @var string
     */
    public $success_url = '';

    /**
     * @var string
     */
    public $fail_url = '';

    /**
     * @var string
     */
    public $status = 'created';
    
    /**
     * @var array
     */
    public static $definition = array(
        'table' => 'novapay_session',
        'primary' => 'id_novapay_session',
        'fields' => array(
            'session_id' => array('type' => self::TYPE_STRING, 'required' => true, 'size' => 50),
            'merchant_id' => array('type' => self::TYPE_STRING, 'required' => true, 'size' => 50),
            'client_first_name' => array('type' => self::TYPE_STRING, 'size' => 20),
            'client_last_name' => array('type' => self::TYPE_STRING, 'size' => 20),
            'client_patronymic' => array('type' => self::TYPE_STRING, 'size' => 20),
            'client_phone' => array('type' => self::TYPE_STRING, 'required' => true, 'size' => 20),
            'status' => array('type' => self::TYPE_STRING, 'required' => true, 'size' => 50),
        ),
    );

    /**
     * @param string $sessionId
     * @return NovaPaySession|null
     */
    public static function getSessionBySessionId($sessionId)
    {
        $query = (new DbQuery())
            ->select('id_novapay_session')
            ->from('novapay_session')
            ->where('session_id = \''.$sessionId.'\'');

        if (!$id = Db::getInstance()->getValue($query->build())) {
            return null;
        }
        
        $session = new NovaPaySession($id);
        if (!Validate::isLoadedObject($session)) {
            return null;
        }

        return $session;
    }
    
    /**
     * @return array
     */
    public function getNovaPayData()
    {
        $data = array(
            'merchant_id' => $this->merchant_id,
            'client_phone' => $this->client_phone,
        );

        if ($this->client_first_name) {
            $data['client_first_name'] = $this->client_first_name;
        }

        if ($this->client_last_name) {
            $data['client_last_name'] = $this->client_last_name;
        }

        if ($this->client_patronymic) {
            $data['client_patronymic'] = $this->client_patronymic;
        }

        if ($this->callback_url) {
            $data['callback_url'] = $this->callback_url;
        }

        if ($this->metadata) {
            $data['metadata'] = json_decode($this->metadata, true);
        }

        if ($this->success_url) {
            $data['success_url'] = $this->success_url;
        }

        if ($this->fail_url) {
            $data['fail_url'] = $this->fail_url;
        }

        return $data;
    }
}
