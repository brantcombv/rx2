<?php

class Rx_Form_Decorator_ElementLine extends Rx_Form_Decorator_HtmlTag
{

    /**
     * Render content wrapped in an HTML tag
     *
     * @param  string $content
     * @return string
     */
    public function render($content)
    {
        // Set additional CSS class to element line tag if element contains errors
        $hasErrors = false;
        $element = $this->getElement();
        if ($element instanceof Zend_Form) {
            $report = $element->getErrors();
            foreach ($report as $field => $errors) {
                if (sizeof($errors)) {
                    $hasErrors = true;
                    break;
                }
            }
        } elseif ($element instanceof Zend_Form_Element) {
            $hasErrors = $element->hasErrors();
        }
        if ($hasErrors) {
            $class = $this->getOption('class');
            $eClass = $this->getOption('errorClass');
            $class = join(' ', array($class, $eClass));
            $this->setOption('class', $class);
        }
        $this->removeOption('errorClass');
        return (parent::render($content));
    }

}
