
const elements = document.querySelectorAll('.add-panier');
let panier = [];
Array.from(elements).forEach(element => {
    element.addEventListener("click", (e) => {
        //  e.preventDefault()
        // addPanier(element.getAttribute("id"))
        //addPanier();

        panier.push(
            {
                id: element.getAttribute("data-id"),
                idType: element.getAttribute("data-id")+element.getAttribute("data-type"),
                nom: element.getAttribute("data-nom"),
                prix: element.getAttribute("data-prix"),
                image: element.getAttribute("data-image"),
                type: element.getAttribute("data-type"),
                quantity: 1,
            }
        )
       
        localStorage.setItem('panier', JSON.stringify(panier));

        // console.log(element.getAttribute("data-nom"));
        // 
        // alert(true);
    })

});


function addPanier() {
    panierData = localStorage.getItem("panier");
    panierData.forEach(element => {
        console.log(element);
    });


}
//addPanier();
function diminuer() {

}
function additionner() {

}

/* function page() {
    
}
async function showPosts() { 
    const posts = await getPosts();
    posts.forEach((post) => {
        postContainer.innerHTML+=`
        <div class="post">
            <div class="number">1</div>
            <div class="post-info">
                <h2 class="post-title">
                    Lorem ipsum dolor sit amet consectetur, adipisicing elit. Ad rem aperiam veritatis necessitatibus natus unde.
                </h2>
                <p class="post-body">
                    Lorem ipsum, dolor sit amet consectetur 
                    adipisicing elit. Odio hic natus dicta veniam omnis. 
                    Error repudiandae debitis facere obcaecati amet totam voluptate 
                    earum exercitationem corporis 
                </p>
            </div>
        </div>
        ,
        `
    });
}
async function getPosts() {
    let post;
    await fetch(url)
    //.then(resp=> console.log(resp))
     .then((resp) => resp.json())
     .then(data=>{
         post = data;
     });
     return post;
       
} */
/* window.addEventListener('scroll',function () {
    const {clientHeight,scrollTop,scrollHeight} = document.documentElement;
  
    if (scrollTop + clientHeight >= scrollHeight - 1510) {
      
    }
   
}) */