<?php

class Rx_View_Helper_Loop extends Zend_View_Helper_Abstract
{
    protected $loopsPath = null;
    protected $suffix = null;

    public function __construct()
    {
        $this->suffix = Zend_Layout::getMvcInstance()->getViewSuffix();
        $this->loopsPath = Rx_Config::get('rx.views.path.loops', 'loops');
    }

    /**
     * Renders template fragment with given model data.
     * Closely resembles standard PartialLoop view helper, but didn't provide
     * separate View for performance reasons
     *
     * @param  string $name         Name of view script
     * @param  array $model         Variables to populate in the view
     * @param  string $var          View variable name to store rendering information to
     *                              null to store information in global scope
     * @throws Rx_Exception
     * @return string
     */
    public function loop($name = null, $model = array(), $var = 'loop')
    {
        $regexp = '/\.' . preg_quote($this->suffix, '/') . '$/i';
        if (!preg_match($regexp, $name)) {
            $name .= '.' . $this->suffix;
        }
        if (!Rx_Path::isAbsolute($name)) {
            $name = Rx_Path::build($this->loopsPath, $name);
        }

        if ((!is_array($model)) &&
            (!$model instanceof Traversable) &&
            ((is_object($model)) && (!method_exists($model, 'toArray')))
        ) {
            throw new Rx_Exception('Loop view helper requires iterable data');
        }

        if ((is_object($model)) &&
            (!$model instanceof Traversable) &&
            (method_exists($model, 'toArray'))
        ) {
            $model = $model->toArray();
        }

        $content = '';
        foreach ($model as $item) {
            if (is_object($item)) {
                if (method_exists($item, 'toArray')) {
                    $item = $item->toArray();
                } else {
                    $item = get_object_vars($item);
                }
            }
            if ($var !== null) {
                $this->view->assign($var, $item);
            } else {
                $this->view->assign($item);
            }
            $content .= $this->view->render($name);
        }

        return ($content);
    }
}
