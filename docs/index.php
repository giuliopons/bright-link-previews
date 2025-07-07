<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
//
// This file parse the HTML of the BrightLinks docs
// add some styles and print everything in HTML.
// 
// The .html file is generated with Google Docs and
// can be sobstituted to update the plugin documentation.
//

//
// get HTML from a file with fopen
$filename = './BrightLinksdocs.html';
$f = fopen($filename, 'r');
$html = fread($f, filesize($filename));
fclose($f);
//
//


$html = str_replace('><h1 ', "><div id='wrap'><h1 ", $html);
$html = str_replace('<head>', "<head><meta name=\"viewport\" content=\"width=device-width, initial-scale=1\">", $html);
$html = str_replace('</body>', "\n\n".'<style>
@font-face {
  font-family: Lexend;
  src: url(./fonts/Lexend-VariableFont_wght.ttf);
  font-weight: normal;
}
span.image {max-width:100%!important;border:1px solid #777!important;margin-top:30px!important; margin-bottom:30px!important}
html,body {background:#eee!important;overflow-x:hidden;margin:0;padding-bottom:100px}
* {box-sizing:border-box}
img {width:100%!important;height:auto!important}
body {   
    max-width: 100%!important;width:100%;
    padding: 0!important;}
@media only screen and (min-width: 600px) {
    body {   
        padding: 5% 15%!important;}
}
#wrap {margin:0px;background:#fff;padding:5%;width:100%;box-shadow: 0px 10px 10px #aaa;}
#gotoindex {     position: fixed;
    bottom: 20px;
    left: 20px;
    background: #073763;
    color: #fff;
    border: 0;
    line-height: 3em;
    padding: 1em;
    border-radius: 0.5em;cursor:pointer}

</style><script>
function addClassToImageSpans() {
    const spans = document.querySelectorAll("span img");
    spans.forEach((img) => {
      const parentSpan = img.parentNode;
      parentSpan.classList.add("image");
    });
  }
  function scrollToTop() {
    // scroll to the top of the document
    const scrollTop = window.scrollY || document.documentElement.scrollTop;
    if (scrollTop > 0) {
      window.requestAnimationFrame(scrollToTop);
      window.scrollTo(0, scrollTop - scrollTop / 8);
    }
  }
  function smoothScroll() {
    const links = document.querySelectorAll("a[href^=\"#\"]");
    links.forEach(link => {
      link.addEventListener("click", function(e) {
        e.preventDefault();
        const href = this.getAttribute("href");
        const target = document.getElementById(href.substring(1));
        const offsetTop = target.offsetTop;
        scroll({
          top: offsetTop,
          behavior: "smooth"
        });
      });
    });
  }
  addClassToImageSpans(); smoothScroll();
</script>
<button onclick="scrollToTop()" id="gotoindex">INDEX</button>
</div></body>', $html);

// Output the modified HTML
echo $html;
?>
