<?php
/**
 * This file is part of phxDoctrineClassFieldsPlugin.
 *
 * phxDoctrineClassFieldsPlugin is free software: you can redistribute it and/or
 * modify it under the terms of the GNU Lesser General Public License as
 * published by the Free Software Foundation, either version 3 of the License,
 * or (at your option) any later version.
 *
 * phxDoctrineClassFieldsPlugin is distributed in the hope that it will be
 * useful, but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU Lesser
 * General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License
 * along with phxDoctrineClassFieldsPlugin.  If not, see
 * <http://www.gnu.org/licenses/>.
 */

/** Injects magic methods into Doctrine_Record.
 *
 * Because of the way Doctrine_Record->__call() works, we can't add __call() to
 *  the template directly; instead, we have to register a "method owner" which
 *  contains the magic method.
 *
 * @author Phoenix Zerin <phoenix@todofixthis.com>
 *
 * @package phxDoctrineClassFieldsPlugin
 * @subpackage lib.doctrine
 */
class ClassFieldsMagic extends Doctrine_Template
{
  protected
    /** @var ClassFields */
    $_template,
    $_methods;

  /** Init the class instance.
   *
   * @param ClassFields $template
   *
   * @return void
   */
  public function __construct( ClassFields $template )
  {
    $this->_template = $template;

    $this->_methods = array();
    foreach( $template->getOption('fields') as $field => $options )
    {
      if( $options['magic'] )
      {
        $meth = (
          is_string($options['magic'])
            ? $options['magic']
            : str_replace(
                '*',
                Doctrine_Inflector::classify($field),
                $template->getOption('magic')
              )
        );

        $template->getTable()->setMethodOwner($meth, $this);
        $this->_methods[$meth] = array('getInstance', array($field));
      }
    }
  }

  /** Process magic method invocation.
   *
   * @param string  $meth
   * @param array   $args
   *
   * @return mixed
   */
  public function __call( $meth, $args )
  {
    if( isset($this->_methods[$meth]) )
    {
      list($callable, $arguments) = $this->_methods[$meth];

      return call_user_func_array(
        array($this->getInvoker(), $callable),
        $arguments
      );
    }

    throw new RuntimeException(sprintf(
      'Method %s was incorrectly wired to %s.  This is a bug with phxDoctrineClassFieldsPlugin; please log an issue on GitHub.',
        $meth,
        get_class($this)
    ));
  }
}