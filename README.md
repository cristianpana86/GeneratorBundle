CPANAGeneratorBundle
===================


This bundle is an extension of [PUGXGeneratorBundle](https://github.com/PUGX/PUGXGeneratorBundle) which is a project improving [SensioGeneratorBundle](https://github.com/sensio/SensioGeneratorBundle).

**CPANAGeneratorBundle** adds to the **Show view** of an entity the associated objects from Bidirectional relations.  
**Example:** there are 2 entities: Author and Book found in One-to-Many  **BIDIRECTIONAL** relation. In 'Author' entity there is a property called 'books' of type ArrayCollection. In the **author/show view** after the fields related to Author there will be listed the Books associated. Also CPANAGeneratorBundle is adding buttons for **Add book**, **view** and **edit**.

**Author**   

Last name: Herbert     
First name: Frank    
Nationality: American   
Id: 1    

**Books**    
[Add book](www.newbook.com)   

Title: Dune Chronicles   
Genre : Science Fiction    
Id:1   
[view](www.viewbook.com)[edit](www.editbook.com)    

Title: Dune Mesiah    
Genre: Science Fiction   
Id: 2    
[view](www.viewbook.com)[edit](www.editbook.com)   

***
PUGXGeneratorBundle adds many functionalities on top of SensioGeneratorBundle:

* main block name customizable in layout
* forms in correct namespace (under Type, not under Form)
* @ParamConverter in actions
* different format for dates/times/datetimes
* include relation fields in show and index templates
* shorter form names
* real entity names instead of "$entity" in actions and templates
* translated texts
* support for form themes (customizable)
* default templates suitable with Boostrap and Font Awesome
* nice "check" icons for boolean fields (when using Font Awesome)
* support for pagination (requires [KnpPaginatorBundle](https://github.com/KnpLabs/KnpPaginatorBundle))
* support for filters (requires [LexikFormFilterBundle](https://github.com/lexik/LexikFormFilterBundle))
* support for sorting
* optional target bundle
* better generated tests
* fixtures generation

Documentation
-------------

[Read the documentation](Resources/doc/index.md)

License
-------

This bundle is released under the LGPL license. See the [complete license text](Resources/meta/LICENSE).

About
-----

PUGXGeneratorBundle is a [PUGX](https://github.com/PUGX) initiative.

See also
--------

For screenshots examples, see [PUGXGeneratorBundleSandbox](https://github.com/garak/PUGXGeneratorBundleSandbox).
