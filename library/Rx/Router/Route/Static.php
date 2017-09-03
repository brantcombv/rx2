<?php

class Rx_Router_Route_Static extends Rx_Router_Route_Base
{

    protected $_route = null;
    protected $_defaults = array();

    /**
     * Prepares the route for mapping.
     *
     * @param string $route   Map used to match with later submitted URL path
     * @param array $defaults Defaults for map variables with keys as variable names
     */
    public function __construct($route, $defaults = array())
    {
        parent::__construct();
        $this->_route = $route;
        $this->_defaults = (array)$defaults;
    }

    public function match($request)
    {
        $this->_match($request);
        $path = $request->getPathInfo();
        if (trim($path, '/') == $this->_route) {
            return ($this->_defaults);
        }
        return (false);
    }

    /**
     * Assembles a URL path defined by this route
     *
     * @param array $data An array of variable and value pairs used as parameters
     * @return string       Route path with user submitted parameters
     */
    public function assemble($data = array(), $reset = false, $encode = false, $partial = false)
    {
        $url = $this->_route;
        return ($this->_assemble($url));
    }

}
