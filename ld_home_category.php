<?php
/**
 * ld_home_category - Linra Digital
 * Bloc "catégories importantes" : 4 blocs fixes (image + libellé + lien).
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

use PrestaShop\PrestaShop\Core\Module\WidgetInterface;

class Ld_Home_Category extends Module implements WidgetInterface
{
    /** Nombre de blocs (fixe : 4 catégories mises en avant) */
    const NB_BLOCKS = 4;

    /** @var string */
    private $templateFile;

    public function __construct()
    {
        $this->name = 'ld_home_category';
        $this->tab = 'front_office_features';
        $this->version = '1.4.0';
        $this->author = 'Linra Digital';
        $this->need_instance = 0;
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('LD - Home Category');
        $this->description = $this->l('Met en avant 4 catégories phares sur la page d\'accueil (image + libellé + lien).');

        $this->ps_versions_compliancy = ['min' => '1.7', 'max' => _PS_VERSION_];

        $this->templateFile = 'module:ld_home_category/ld_home_category.tpl';
    }

    public function install()
    {
        $this->_clearCache($this->templateFile);

        if (!parent::install()
            || !$this->registerHook('displayHome')
            || !$this->registerHook('actionFrontControllerSetMedia')
        ) {
            return false;
        }

        // Textes multilingues : chaque valeur doit être un tableau [id_lang => texte].
        // Libellés neutres, à personnaliser dans la configuration du module.
        $languages = Language::getLanguages(false);
        $perLang = function ($text) use ($languages) {
            $values = [];
            foreach ($languages as $lang) {
                $values[(int) $lang['id_lang']] = $text;
            }

            return $values;
        };

        for ($i = 1; $i <= self::NB_BLOCKS; $i++) {
            Configuration::updateValue('LD_HOMECAT_LABEL_' . $i, $perLang('Catégorie ' . $i));
            Configuration::updateValue('LD_HOMECAT_LINK_' . $i, $perLang(''));
            Configuration::updateValue('LD_HOMECAT_DESC_' . $i, $perLang(''));
        }

        Configuration::updateValue('LD_HOMECAT_BTN_TEXT', $perLang('Découvrir'));
        Configuration::updateValue('LD_HOMECAT_EYEBROW', $perLang('Sélection'));
        Configuration::updateValue('LD_HOMECAT_TITLE', $perLang('Nos catégories'));
        Configuration::updateValue('LD_HOMECAT_INTRO', $perLang(''));

        return true;
    }

    public function uninstall()
    {
        $img_dir = $this->getLocalPath() . 'img/';

        for ($i = 1; $i <= self::NB_BLOCKS; $i++) {
            // Image envoyée pour ce bloc
            $img = Configuration::get('LD_HOMECAT_IMG_' . $i);
            if ($img && is_file($img_dir . basename($img))) {
                @unlink($img_dir . basename($img));
            }

            Configuration::deleteByName('LD_HOMECAT_IMG_' . $i);
            Configuration::deleteByName('LD_HOMECAT_LABEL_' . $i);
            Configuration::deleteByName('LD_HOMECAT_LINK_' . $i);
            Configuration::deleteByName('LD_HOMECAT_DESC_' . $i);
        }

        Configuration::deleteByName('LD_HOMECAT_BTN_TEXT');
        Configuration::deleteByName('LD_HOMECAT_EYEBROW');
        Configuration::deleteByName('LD_HOMECAT_TITLE');
        Configuration::deleteByName('LD_HOMECAT_INTRO');

        return parent::uninstall();
    }

    /**
     * ------------------------------------------------------------------
     * Back office : formulaire de configuration
     * ------------------------------------------------------------------
     */
    public function getContent()
    {
        $output = '';

        if (Tools::isSubmit('submitLdHomeCategory')) {
            $this->postProcess();
            $output .= $this->displayConfirmation($this->l('Configuration mise à jour.'));
            $this->_clearCache($this->templateFile);
        }

        return $output . $this->renderForm();
    }

    public function postProcess()
    {
        $languages = Language::getLanguages(false);
        $img_dir = $this->getLocalPath() . 'img/';

        if (!file_exists($img_dir)) {
            mkdir($img_dir, 0755, true);
        }

        $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

        for ($i = 1; $i <= self::NB_BLOCKS; $i++) {
            // --- Image (non multilingue, un seul fichier par bloc) ---
            $field = 'LD_HOMECAT_IMG_' . $i;

            if (isset($_FILES[$field]) && !empty($_FILES[$field]['tmp_name'])) {
                $ext = strtolower(pathinfo($_FILES[$field]['name'], PATHINFO_EXTENSION));

                if ($error = ImageManager::validateUpload($_FILES[$field], 4000000)) {
                    $this->context->controller->errors[] = $error;
                } elseif (!in_array($ext, $allowed_extensions, true)) {
                    $this->context->controller->errors[] = $this->l('Format d\'image non autorisé (JPG, PNG, GIF ou WebP).');
                } else {
                    $file_name = 'homecat_' . $i . '_' . md5($_FILES[$field]['name'] . microtime()) . '.' . $ext;

                    if (move_uploaded_file($_FILES[$field]['tmp_name'], $img_dir . $file_name)) {
                        $old = Configuration::get($field);
                        if ($old && is_file($img_dir . basename($old))) {
                            @unlink($img_dir . basename($old));
                        }
                        Configuration::updateValue($field, $file_name);
                    }
                }
            }

            // --- Libellé / description / lien (multilingues : tableaux [id_lang => texte]) ---
            $labels = [];
            $descriptions = [];
            $links = [];

            foreach ($languages as $lang) {
                $id_lang = (int) $lang['id_lang'];
                $labels[$id_lang] = Tools::getValue('LD_HOMECAT_LABEL_' . $i . '_' . $id_lang);
                $descriptions[$id_lang] = Tools::getValue('LD_HOMECAT_DESC_' . $i . '_' . $id_lang);
                $links[$id_lang] = $this->sanitizeLink(Tools::getValue('LD_HOMECAT_LINK_' . $i . '_' . $id_lang));
            }

            Configuration::updateValue('LD_HOMECAT_LABEL_' . $i, $labels);
            Configuration::updateValue('LD_HOMECAT_DESC_' . $i, $descriptions);
            Configuration::updateValue('LD_HOMECAT_LINK_' . $i, $links);
        }

        // --- Bloc "information globale" (au-dessus des 4 blocs) et texte du bouton, communs aux 4 blocs ---
        $texts = ['BTN_TEXT' => [], 'EYEBROW' => [], 'TITLE' => [], 'INTRO' => []];

        foreach ($languages as $lang) {
            $id_lang = (int) $lang['id_lang'];
            foreach (array_keys($texts) as $key) {
                $texts[$key][$id_lang] = Tools::getValue('LD_HOMECAT_' . $key . '_' . $id_lang);
            }
        }

        foreach ($texts as $key => $values) {
            Configuration::updateValue('LD_HOMECAT_' . $key, $values);
        }
    }

    /**
     * Un lien doit être vide, une adresse du site (/chemin) ou une URL http(s) : tout autre schéma
     * (javascript:, data:...) est refusé.
     */
    private function sanitizeLink($link)
    {
        $link = trim((string) $link);

        if ($link === '') {
            return '';
        }

        return (preg_match('#^(https?://|/)#i', $link) && Validate::isUrl($link)) ? $link : '';
    }

    public function renderForm()
    {
        $input = [];

        $input[] = [
            'type' => 'text',
            'label' => $this->l('Sur-titre'),
            'name' => 'LD_HOMECAT_EYEBROW',
            'lang' => true,
            'desc' => $this->l('Ex : "Sélection" - petit texte au-dessus du titre.'),
        ];
        $input[] = [
            'type' => 'text',
            'label' => $this->l('Titre'),
            'name' => 'LD_HOMECAT_TITLE',
            'lang' => true,
            'desc' => $this->l('Ex : "PRODUITS & PIÈCES".'),
        ];
        $input[] = [
            'type' => 'textarea',
            'label' => $this->l('Description'),
            'name' => 'LD_HOMECAT_INTRO',
            'lang' => true,
            'autoload_rte' => false,
            'rows' => 3,
        ];


        for ($i = 1; $i <= self::NB_BLOCKS; $i++) {
            $input[] = [
                'type' => 'file_img',
                'label' => sprintf($this->l('Bloc %d - Image'), $i),
                'name' => 'LD_HOMECAT_IMG_' . $i,
                'desc' => $this->l('Image de fond du bloc (format libre, à cadrer/ajuster en CSS).'),
            ];
            $input[] = [
                'type' => 'text',
                'label' => sprintf($this->l('Bloc %d - Libellé'), $i),
                'name' => 'LD_HOMECAT_LABEL_' . $i,
                'lang' => true,
            ];
            $input[] = [
                'type' => 'text',
                'label' => sprintf($this->l('Bloc %d - Description'), $i),
                'name' => 'LD_HOMECAT_DESC_' . $i,
                'lang' => true,
                'desc' => $this->l('Ex : "Route / Circuit / Rallye".'),
            ];
            $input[] = [
                'type' => 'text',
                'label' => sprintf($this->l('Bloc %d - Lien'), $i),
                'name' => 'LD_HOMECAT_LINK_' . $i,
                'lang' => true,
                'desc' => $this->l('Laisser vide pour un bloc non cliquable.'),
            ];
        }

        $input[] = [
            'type' => 'text',
            'label' => $this->l('Texte du bouton (commun aux 4 blocs)'),
            'name' => 'LD_HOMECAT_BTN_TEXT',
            'lang' => true,
            'desc' => $this->l('Ex : "Découvrir". Affiché uniquement sur les blocs avec un lien renseigné.'),
        ];

        $fields_form = [
            'form' => [
                'legend' => [
                    'title' => $this->l('LD - Home Category'),
                    'icon' => 'icon-th-large',
                ],
                'input' => $input,
                'submit' => [
                    'title' => $this->l('Enregistrer'),
                ],
            ],
        ];

        $helper = new HelperForm();
        $helper->show_toolbar = false;
        $helper->table = $this->table;
        $lang = new Language((int) Configuration::get('PS_LANG_DEFAULT'));
        $helper->default_form_language = $lang->id;
        $helper->module = $this;
        $helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG')
            ? Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG')
            : 0;

        $helper->identifier = $this->identifier;
        $helper->submit_action = 'submitLdHomeCategory';
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false)
            . '&configure=' . $this->name
            . '&tab_module=' . $this->tab
            . '&module_name=' . $this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');

        $helper->tpl_vars = [
            'uri' => $this->getPathUri(),
            'fields_value' => $this->getConfigFieldsValues(),
            'languages' => $this->context->controller->getLanguages(),
            'id_language' => $this->context->language->id,
        ];

        return $helper->generateForm([$fields_form]);
    }

    public function getConfigFieldsValues()
    {
        $languages = Language::getLanguages(false);
        $fields = [];

        for ($i = 1; $i <= self::NB_BLOCKS; $i++) {
            $fields['LD_HOMECAT_IMG_' . $i] = Configuration::get('LD_HOMECAT_IMG_' . $i);

            foreach ($languages as $lang) {
                $id_lang = (int) $lang['id_lang'];
                $fields['LD_HOMECAT_LABEL_' . $i][$id_lang] = Configuration::get('LD_HOMECAT_LABEL_' . $i, $id_lang);
                $fields['LD_HOMECAT_DESC_' . $i][$id_lang] = Configuration::get('LD_HOMECAT_DESC_' . $i, $id_lang);
                $fields['LD_HOMECAT_LINK_' . $i][$id_lang] = Configuration::get('LD_HOMECAT_LINK_' . $i, $id_lang);
            }
        }

        foreach ($languages as $lang) {
            $id_lang = (int) $lang['id_lang'];
            $fields['LD_HOMECAT_BTN_TEXT'][$id_lang] = Configuration::get('LD_HOMECAT_BTN_TEXT', $id_lang);
            $fields['LD_HOMECAT_EYEBROW'][$id_lang] = Configuration::get('LD_HOMECAT_EYEBROW', $id_lang);
            $fields['LD_HOMECAT_TITLE'][$id_lang] = Configuration::get('LD_HOMECAT_TITLE', $id_lang);
            $fields['LD_HOMECAT_INTRO'][$id_lang] = Configuration::get('LD_HOMECAT_INTRO', $id_lang);
        }

        return $fields;
    }

    /**
     * ------------------------------------------------------------------
     * Front office
     * ------------------------------------------------------------------
     */
    public function hookDisplayHome($params)
    {
        return $this->renderWidget('displayHome', $params);
    }

    public function hookActionFrontControllerSetMedia($params)
    {
        $this->context->controller->registerStylesheet(
            'ld-home-category-css',
            'modules/' . $this->name . '/views/css/ld_home_category.css',
            ['media' => 'all', 'priority' => 100]
        );
    }

    /**
     * ------------------------------------------------------------------
     * WidgetInterface
     * ------------------------------------------------------------------
     */
    public function renderWidget($hookName, array $configuration)
    {
        if (!$this->isCached($this->templateFile, $this->getCacheId('ld_home_category'))) {
            $variables = $this->getWidgetVariables($hookName, $configuration);
            if (empty($variables['ld_categories'])) {
                return false;
            }
            $this->smarty->assign($variables);
        }

        return $this->fetch($this->templateFile, $this->getCacheId('ld_home_category'));
    }

    public function getWidgetVariables($hookName, array $configuration)
    {
        $id_lang = $this->context->language->id;
        $categories = [];

        for ($i = 1; $i <= self::NB_BLOCKS; $i++) {
            $img = Configuration::get('LD_HOMECAT_IMG_' . $i);

            $categories[] = [
                'image' => $img ? $this->_path . 'img/' . $img : '',
                'label' => Configuration::get('LD_HOMECAT_LABEL_' . $i, $id_lang),
                'description' => Configuration::get('LD_HOMECAT_DESC_' . $i, $id_lang),
                'link' => Configuration::get('LD_HOMECAT_LINK_' . $i, $id_lang),
            ];
        }

        // Si aucune image n'est configurée sur aucun bloc, on n'affiche rien
        $has_image = false;
        foreach ($categories as $cat) {
            if (!empty($cat['image'])) {
                $has_image = true;
                break;
            }
        }

        if (!$has_image) {
            return [];
        }

        return [
            'ld_categories' => $categories,
            'ld_homecat_btn_text' => Configuration::get('LD_HOMECAT_BTN_TEXT', $id_lang),
            'ld_homecat_eyebrow' => Configuration::get('LD_HOMECAT_EYEBROW', $id_lang),
            'ld_homecat_title' => Configuration::get('LD_HOMECAT_TITLE', $id_lang),
            'ld_homecat_intro' => Configuration::get('LD_HOMECAT_INTRO', $id_lang),
        ];
    }
}
