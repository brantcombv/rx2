<?php

class Rx_Form_Element_Radio extends Rx_Form_Element_Multi
{
    /**
     * Use formRadio view helper by default
     *
     * @var string
     */
    public $helper = 'formRadio';

    /**
     * Load default decorators
     *
     * Disables "for" attribute of label if label decorator enabled.
     *
     * @return $this
     */
    public function loadDefaultDecorators()
    {
        if ($this->loadDefaultDecoratorsIsDisabled()) {
            return $this;
        }
        parent::loadDefaultDecorators();
        $this->addDecorator('Label', array(
            'tag'        => 'dt',
            'disableFor' => true
        ));
        return $this;
    }

}
