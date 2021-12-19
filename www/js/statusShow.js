"use strict";

const showGreen = document.getElementById("sGreenLink");

const statusP = document.getElementById("statusP");
const statusN = document.getElementById("statusN");

statusN.style.display = "block";
statusP.style.display = "none";

showGreen.addEventListener("click", () => {
    statusN.style.display = "none";
    statusP.style.display = "block";
})