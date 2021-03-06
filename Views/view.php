doctype html
html
  head
    title Examples
    meta name="keywords" content="template language"
    meta name="author" content="author"
    script src="script.js"

  body
    h1 Markup examples
    #content
      p This example shows you how a basic file looks like.


      - if !empty($items)
        table
          - foreach $items as $name => $price
            tr
              td.name = $name
              td.price = $price
        @block:testblock
      - else
        p
          | No items found.  Please add some inventory.
          | Thank you!
    div id="footer"
      @render: 'Views/footer.php'
      | Copyright © #{$year} #{$author}t