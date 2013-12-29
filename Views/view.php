doctype html
html
  head
    title Slim Examples
    meta name="keywords" content="template language"
    meta name="author" content="author"
    javascript:
      | alert('Slim supports embedded javascript!')

  body
    h1 Markup examples
    - $items = array(1)
    #content
      p This example shows you how a basic Slim file looks like.


      - if !empty($items)
        table
          - foreach $items as $item
            tr
              td.name
                = $item
              td.price
                = $item
      - else
        p
          | No items found.  Please add some inventory.
          | Thank you!

    div id="footer"
      render 'footer'
      | Copyright © #{year} #{author}t