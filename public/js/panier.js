let panier = document.getElementsByClassName("panier")
/* (function($){
    $(".panier").click(function (e) { 
        e.preventDefault();
        $.get($(this).attr('href'),{},function (param) 
        {
            location.href = 'home/index.html.twig'
        })
    });
}) */
for (let i = 0; i < panier.length; i++) {
    panier[i].addEventListener('click', (e)=> {
       e.preventDefault();
    });
   
}
//event
   /*  panier.addEventListener('click',function(e){ 
       console.log(true); 
    }); */
/* let panierData =[];
if (localStorage.getItem('panierData')) {
    panierData = JSON.parse(localStorage.getItem('panierData'));
}
for (let i = 0; i < panier.length; i++) {
    panier[i].addEventListener('click', (e)=> {
        panierData.push(
            {product:panier[i].getAttribute("id"),quantite:1}
        );
    });
   
}
console.log(panierData); */

   /*  panier.forEach(item => {
        item.addEventListener('click',(e) => {
            e.preventDefault();
            panierData.push(
                {product:item.getAttribute("id"),quantity:1}
            );
            ;
                console.log(localStorage.getItem('panierData'));
        });
      
    }); */
/* let currpage    = window.location.href;
let lasturl     = sessionStorage.getItem("last_url");
 if(lasturl == null || lasturl.length === 0 || currpage !== lasturl ){
    sessionStorage.setItem("last_url", currpage);

}
  */
function getPanier() {
    let panier = localStorage.getItem('panierData');
    if (panier == null) {
        return [];
    }else{
        return JSON.parse(panier);
    }
}
function addPanier(product) {
    let panier = getPanier();
    let panierFound = panier.find(p => p.id == product.id);
    if (panierFound != undefined) {
        panierFound.quantity++;
    } else {
        panierFound.quantity =1;
        panier.push(product);
    }
}

function remove() {
    
}
