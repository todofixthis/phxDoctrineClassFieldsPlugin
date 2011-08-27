# About

phxDoctrineClassFieldsPlugin adds the concept of "class" fields to Doctrine 1.2,
  allowing you to implement database-backed dependency injection for controlling
  the behavior of containers at runtime.

Doctrine 1.2 does not support custom field types, so this is not a "true" custom
  field type, but it is a good imitation.

# Usage

Once the plugin has been enabled in your Symfony project configuration, you can
  add the behavior to any of your models in `schema.yml`:

    # sf_config_dir/doctrine/schema.yml

    MyModel:
      actAs:
        ClassFields:
          fields:
            plugin:
              interface:  IMyPlugin

Store class names as normal:

    # .../actions.class.php

    public function executeAwesome( sfWebRequest $request )
    {
      ...

      /* @var $mymodel MyModel */
      $mymodel->setPlugin('MyPluginClass');
    }

To instantiate DI class instances, use `getInstance` or `get*Instance`:

    # .../actions.class.php

    public function executeExcellence( sfWebRequest $request )
    {
      ...

      /* @var $myplugin MyPluginClass */
      $myplugin = $mymodel->getInstance('plugin');

      /* This does the same thing: */
      $myplugin = $mymodel->getPluginInstance();
    }

You can also customize the initialization of the DI instance by overriding the
  `postGetInstance()` method in your model:

    # sf_lib_dir/model/doctrine/MyModel.php

    class MyModel extends BaseMyModel
    {
      ...

      public function postGetInstance( $instance )
      {
        $instance->setFoo($this->getBar());
      }
    }

Because of the way Doctrine_Template works, `postGetInstance()` must be public.

## Options

The `ClassFields` behavior accepts the following options:

- `fields`: array of fields that will have the `class` "type".
  - `index`:  name of the index for the class field.  If blank, the field is unindexed (default: `""`).
  - `interface`:  interface that a stored classname must implement to be valid (default: `null`).
  - `length`:  maximum length of class names (default: `200`).
  - `magic`:  whether to create magic methods for instantiating the class (default: `true`). If set to a string value, it will override the name of the magic method for this column.
  - `notnull`:  whether the classname must not be empty (default: `false`).
  - `unique`: whether the classname must be unique (default: `false`).
- `magic`: whether to allow creating magic methods for any column (default: `true`).  If set to a string value, it will override the magic method naming pattern for the table.

Note that `magic` only controls the creation of `get*Instance()` methods.  The
  `getInstance()` method will always be available.

Both the table's `magic` option and the individual column's `magic` option must
  be set to `true`, or else magic methods will not be created for that column.

Fully-loaded example:

    # sf_config_dir/doctrine/schema.yml

    MyModel:
      actAs:
        ClassFields:
          magic:  instantiate*Object
          fields:
            transport:
              interface:  ITransport
              notnull:    true
            adapter:
              interface:  IMyModelAdapter
              magic:      getAdapterObject  # For compatiblity with legacy code.
              length:     300               # These names tend to get lengthy.
              index:      adapter_classname_idx
              unique:     true
      columns:
        ...

Note that `ClassFields` will define the class columns and indexes for you
  automatically; there is no need to add them to your model's `columns` and
  `indexes` definitions, respectively.

# Known Issues

- Magic methods for instantiating from relations are not supported... yet.

# Changelog

## 1.0.0

- Initial release.