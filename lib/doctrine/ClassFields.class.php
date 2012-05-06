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

/** Configures a model for use with the class fields behavior.
 *
 * @author Phoenix Zerin <phoenix@todofixthis.com>
 *
 * @package phxDoctrineClassFieldsPlugin
 * @subpackage lib
 */
class ClassFields
  extends Doctrine_Template
{
  protected
    $_options = array(
        'fields'  => array()
      , 'magic'   => 'get*Instance'
    );

  /** Applies custom table configuration
   *
   * @throws InvalidArgumentException If options are malformed.
   * @return void
   */
  public function setTableDefinition(  )
  {
    $defaults = array(
        'index'     => ''
      , 'interface' => null
      , 'length'    => 200
      , 'magic'     => true
      , 'notnull'   => false
      , 'default'   => null
      , 'unique'    => false
    );

    if( ! $fields = $this->getOption('fields') )
    {
      throw new InvalidArgumentException(sprintf(
        '%s template specified for %s, but no class fields were defined.'
          , get_class($this)
          , $this->getTable()->getTableName()
      ));
    }

    foreach( $fields as $field => $options )
    {
      /* Quick validation. */
      if( ! is_array($options) )
      {
        throw new InvalidArgumentException(sprintf(
          'Invalid format for %s ClassFields field; array value expected.'
            , $field
        ));
      }

      $options = array_merge(
          $defaults
        , array_intersect_key($options, $defaults)
      );

      /* Store validated options back to $_options. */
      $this->_options['fields'][$field] = $options;

      /* Configure column for class field. */
      $columnOptions = array(
          'notnull' => (bool) $options['notnull']
        , 'default' => $options['default']
      );

      if( $options['unique'] and ! $options['index'] )
      {
        $columnOptions['unique'] = true;
      }

      /* Create column. */
      $this->hasColumn($field, 'string', $options['length'], $columnOptions);

      /* Add index if applicable. */
      if( $options['index'] )
      {
        $indexOptions = array(
          'fields'  => array($field)
        );

        if( $options['unique'] )
        {
          $indexOptions['type'] = 'unique';
        }

        $this->index($options['index'], $indexOptions);
      }
    }

    if( $this->getOption('magic') )
    {
      new ClassFieldsMagic($this);
    }
  }

  /** Return the instance for a class field.
   *
   * @param string    $field
   * @param mixed,...         Additional parameters passed to the
   *  postGetInstance hook.
   *
   * @return mixed
   * @throws LogicException if the specified field is not a class field.
   * @throws RuntimeException if the stored class name is invalid.
   */
  public function getInstance( $field /*, ... */ )
  {
    /* Validate $field. */
    if( isset($this->_options['fields'][$field]) )
    {
      $options  = $this->_options['fields'][$field];
      $record   = $this->getInvoker();

      /* Get class name from record. */
      if( $class = $record->get($field) )
      {
        /* Validate classname. */
        if( ! class_exists($class) )
        {
          throw new RuntimeException(sprintf(
            'No such class "%s".'
              , $class
          ));
        }

        /* Validate classname against interface if specified. */
        if( $interface = $options['interface'] )
        {
          $ref = new ReflectionClass($class);
          if( ! $ref->implementsInterface($interface) )
          {
            throw new RuntimeException(sprintf(
              'Invalid class %s; %s expected.'
                , $class
                , $interface
            ));
          }
        }


        $obj = new $class();

        /* Post-process and return. */
        $args     = func_get_args();
        $args[0]  = $obj;

        $alt = call_user_func_array(array($record, 'postGetInstance'), $args);

        return (is_null($alt) ? $obj : $alt);

      }

      return null;
    }

    throw new LogicException(sprintf(
      'Field %s is not a class field.'
        , $field
    ));
  }

  /** Perform any additional initialization after {@see getInstance()} creates a
   *    class instance, but before it returns.
   *
   * Override this method in your model class if desired.
   *
   * @param object    $instance
   * @param mixed,... Additional parameters that were passed to getInstance().
   *  Note that there are no guarantees that any of these are present or valid.
   *
   * @return mixed usually void, but if this method returns anything, it will
   *  override what getInstance() sends back.
   *
   * @access protected only marked public because of the way Doctrine works, but
   *  this method should not be considered part of the model class' API.
   */
  public function postGetInstance( $instance /*, ... */ )
  {
  }
}