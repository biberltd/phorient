# Phorient
This bundle is an attempt to create an ODM which is for Orient DB database. The ODM is inspired by and depends on several libraries 
of Docreine ORM as well as Ostico's PHPOrient bundle.


# Parameters
```
orientdb:
        root:
            username: root
            password: root
        database:
            Dbname:
                username: root
                password: root
                hostname: localhost
                port: 2424
                token: null
```
                
# Using

```
$this->cm = new ClassManager($containerInterface);
$this->cm->setEntityPath('AppBundle','\\AppBundle\\Entity\\');
$this->cm->createConnection('Dbname');

```
or

```
$config = array(
  'database' => array(
    'Dbname' => array(
      'username'=>'root',
      'password'=>'root',
      'hostname'=>'localhost',
      'port'=>2424,
      'token'=>null
     )
  )
);
$this->cm = new ClassManager();
$this->cm->setEntityPath('AppBundle','\\AppBundle\\Entity\\');
$this->cm->createConnection('Dbname',$config);
```
        
# LICENSE
Copyright 2017 Biber Ltd. (www.biberltd.com), founding partner of BO Development Office (www.bodevoffice.com)

Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated documentation files 
(the "Software"), to deal in the Software without restriction, including without limitation the rights to use, copy, modify, merge, 
publish, distribute, sublicense, and/or sell copies of the Software, and to permit persons to whom the Software is furnished to do so, 
subject to the following conditions:

The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF 
MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR 
ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH 
THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
