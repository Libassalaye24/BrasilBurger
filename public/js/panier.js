

const panierAdd = document.getElementsByClassName('addPanier');
let countPanier = 0;
let productData = []; 
for (let i = 0; i < panierAdd.length; i++) {

    panierAdd[i].addEventListener("click",(e)=>{
        countPanier++;
        localStorage.setItem('panier',countPanier);
         addPanier(panierAdd[i]); 
      /*   if (productData[0] ==  panierAdd[i].getAttribute("id")) {
            
        }
        productData.push(
            panierAdd[i].getAttribute("id"),
        ); */
       // localStorage.setItem('productData',productData);
    });
}

function addPanier(id) 
{
    panier = localStorage.getItem('productData');
    if (!panier[0]) {
        panier[0]++;
    }else{
        productData.push(
            id.getAttribute("id"),
        );
    }
   /*  productData.push(
        {product:product.getAttribute("id"),quantity:1}
    ); */
    localStorage.setItem('productData',panier);
}
function updatePanier(params=null) {
    let value = 0;
    if (params == null) {
        value++;
    }else{
        value--;
    }
    localStorage.setItem('value',value);
   
}
/* panierNumber.innerText = '1';
 */// 
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
/* function getPanier() {
    let panier = localStorage.getItem('panierData');
    if (panier == null) {
        return [];
    }else{
        return JSON.parse(panier);
    }
} */
/* function addPanier(product) {
    let panier = getPanier();
    let panierFound = panier.find(p => p.id == product.id);
    if (panierFound != undefined) {
        panierFound.quantity++;
    } else {
        panierFound.quantity =1;
        panier.push(product);
    }
} */


