'use strict';

/**
 * Toolkit for ajax forms
 */
const Formajax = function() {
    /** {HTMLFormElement} form with params to send for queries like conc */
    var form = false;
    /** array of {HTMLDivElement} with html updates */
    var divs = {};
    /** Message send to a callback loader to say en of file */
    const EOF = '\u000A';
    /** Used as a separator between mutiline <div> */
    const LF = '&#10;';

    /**
     * Get URL and send line by line to a callback function.
     * “Line” separator could be configured with any string,
     * this allow to load multiline html chunks 
     * 
     * @param {String} url 
     * @param {function} callback 
     * @returns 
     */
    function loadLines(url, callback, sep = '\n') {
        return new Promise(function(resolve, reject) {
            var xhr = new XMLHttpRequest();
            var start = 0;
            xhr.onprogress = function() {
                // loop on separator
                var end;
                while ((end = xhr.response.indexOf(sep, start)) >= 0) {
                    callback(xhr.response.slice(start, end));
                    start = end + sep.length;
                }
            };
            xhr.onload = function() {
                let part = xhr.response.slice(start);
                if (part.trim()) callback(part);
                // last, send a message to callback
                callback(EOF);
                resolve();
            };
            xhr.onerror = function() {
                reject(Error('Connection failed'));
            };
            xhr.responseType = 'text';
            xhr.open('GET', url);
            xhr.send();
        });
    }


    /**
     * Load Json complete, not in slices
     * @param {*} url 
     * @param {*} callback 
     */
    function loadJson(url, callback) {
        var xhr = new XMLHttpRequest();
        xhr.open('GET', url, true);
        xhr.responseType = 'json';
        xhr.onload = function() {
            var status = xhr.status;
            if (status === 200) {
                callback(xhr.response, null);
            } else { // in case of error ?
                callback(xhr.response, status);
            }
        };
        xhr.send();
    }

    /**
     * Get form values as url pars
     */
    function pars(...include) {
        const formData = new FormData(form);
        // delete empty values, be careful, deletion will modify iterator
        const keys = Array.from(formData.keys());
        for (const key of keys) {
            if (include.length > 0 && !include.find(k => k === key)) {
                formData.delete(key);
            }
            if (!formData.get(key)) {
                formData.delete(key);
            }
        }
        return new URLSearchParams(formData);
    }

    /**
     * Update interface with data
     */
    function update(pushState = true) {
        for (let key in divs) upDiv(key);
        if (pushState) urlUp();
    }

    /**
     * Intitialize an input with suggest
     * @param {HTMLInputElement} input 
     * @returns 
     */
    function suggestInit(input) {
        if (!input) {
            console.log("[Formajax] No <input> to equip");
            return;
        }
        if (input.list) { // create a list
            console.log("[Formajax] <datalist> is bad for filtering\n" + input);
        }
        if (!input.dataset.url) {
            console.log("[Formajax] No @data-url to get data from\n" + input);
            return;
        }
        if (!input.dataset.name) {
            console.log("[Formajax] No @data-name to create params\n" + input);
            return;
        }
        input.autocomplete = 'off';
        // create suggest
        const suggest = document.createElement("div");
        suggest.className = "suggest " + input.dataset.name;
        input.parentNode.insertBefore(suggest, input.nextSibling);
        input.suggest = suggest;
        suggest.input = input;
        suggest.hide = suggestHide;
        suggest.show = suggestShow;
        // global click hide current suggest
        window.addEventListener('click', (e) => {
            if (window.suggest) window.suggest.hide();
        });
        // click in suggest, avoid hide effect at body level
        input.parentNode.addEventListener('click', (e) => {
            e.stopPropagation();
        });
        // control suggests, 
        input.addEventListener('click', function(e) {
            if (suggest.style.display != 'block') {
                suggest.show();
            } else {
                suggest.hide();
            }
        });

        input.addEventListener('click', suggestFill);
        input.addEventListener('input', suggestFill);
        input.addEventListener('input', function(e) { suggest.show(); });

        suggest.addEventListener("touchstart", function(e) {
            // si on défile la liste de résultats sur du tactile, désafficher le clavier
            input.blur();
        });
        input.addEventListener('keyup', function(e) {
            e = e || window.event;
            if (e.key == 'Esc' || e.key == 'Escape') {
                suggest.hide();
            } else if (e.key == 'Backspace') {
                if (input.value) return;
                suggest.hide();
            } else if (e.key == 'ArrowDown') {
                if (input.value) return;
                suggest.show();
            } else if (e.key == 'ArrowUp') {
                // focus ?
            }
        });
    }



    /**
     * Append a line record to a suggest
     * @param {HTMLDivElement} suggest block where to append suggestions 
     * @param {*} line 
     */
    function suggestLine(suggest, json) {
        if (!json.trim()) { // sometimes empty
            return;
        }
        try {
            var data = JSON.parse(json);
        } catch (err) {
            console.log(Error('parsing: "' + json + "\"\n" + err));
            return;
        }
        // maybe meta
        if (!data.text || !data.id) {
            return;
        }

        let facet = document.createElement('div');
        facet.className = "facet";
        const hits = (data.hits) ? " (" + data.hits + ")" : "";
        if (data.html) {
            facet.innerHTML = data.html + hits;
        } else if (data.text) {
            facet.innerHTML = data.text + hits;
        } else { // ?? bad !
            facet.innerHTML = data.id + hits;
        }
        facet.dataset.id = data.id;
        facet.addEventListener('click', facetPush);
        facet.input = suggest.input;
        suggest.appendChild(facet);
    }

    /**
     * Start population of a suggester 
     * @param {Event} e 
     */
    function suggestFill(e) {
        const input = e.currentTarget;
        const suggest = input.suggest;
        // get forms params
        const formData = new FormData(input.form);
        const pars = new URLSearchParams(formData);
        pars.set("glob", input.value); // add the suggest query

        // search form sender and receiver
        const url = input.dataset.url + "?" + pars;
        suggest.innerText = '';
        loadLines(url, function(json) {
            suggestLine(suggest, json);
        });
    }

    function urlUp() {
        const url = new URL(window.location);
        url.search = pars();
        window.history.pushState({}, '', url);
    }

    /**
     * Delete an hidden field
     * @param {Event} e 
     */
    function inputDel(e) {
        const label = e.currentTarget.parentNode;
        label.parentNode.removeChild(label);
        update(true);
    }

    /**
     * Push a value for a facet
     * @param {Event} e 
     */
    function facetPush(e) {
        const facet = e.currentTarget;
        const label = document.createElement("label");
        label.className = 'facet';
        const a = document.createElement("a");
        a.innerText = '🞭';
        a.className = 'inputDel';
        a.addEventListener('click', inputDel);
        label.appendChild(a);
        const input = document.createElement("input");
        input.name = facet.input.dataset.name;
        input.type = 'hidden';
        input.value = facet.dataset.id;
        label.appendChild(input);
        const text = document.createTextNode(facet.textContent.replace(/ *\(\d+\) *$/, ''));
        label.appendChild(text);
        facet.input.parentNode.insertBefore(label, facet.input);
        facet.input.focus();
        facet.input.suggest.hide();
        update(true); // update interface
    }

    /**
     * Attached to a suggest pannel, hide
     */
    function suggestHide() {
        const suggest = this;
        suggest.blur();
        suggest.style.display = 'none';
        suggest.input.value = '';
        window.suggest = null;
    }

    /**
     * Attached to a suggest pannel, show
     */
    function suggestShow() {
        const suggest = this;
        if (window.suggest && window.suggest != suggest) {
            window.suggest.hide();
        }
        window.suggest = suggest;
        suggest.style.display = 'block';
    }

    /**
     * 
     * @param {*} form 
     * @returns 
     */
    function init(el) {
        if (!el) {
            console.log('[FormAjax] A <form> is required to init FormAjax');
            return;
        }
        form = el;
    }


    /**
     * Record a div to be updated by an url
     * @param {*} div 
     * @returns 
     */
    function divSetup(id) {
        const div = document.getElementById(id);
        if (!div) { // no pb, it’s another kind of page
            return;
        }
        if (!div.dataset.url) {
            console.log('[Elicom] @data-url required <div data-url="data/conc">');
        }
        divs[id] = div;
    }

    /**
     * Send query to populate concordance
     * @param {boolean} append 
     */
    function upDiv(key, append = false) {
        let div = divs[key];
        if (!div) return; // disappeared ?
        if (div.loading) return; // still loading
        div.loading = true;
        if (!append) {
            div.innerText = '';
        }
        let url = div.dataset.url + "?" + pars();
        loadLines(url, function(html) {
            insLine(div, html);
        }, LF);
    }

    /**
     * Append a record to a div
     * @param {*} html 
     * @returns 
     */
    function insLine(div, html) {
        if (!div) { // what ?
            return false;
        }
        // last line, liberate div for next load
        if (html == EOF) {
            div.loading = false;
            return;
        }
        div.insertAdjacentHTML('beforeend', html);
    }



    return {
        divSetup: divSetup,
        init: init,
        inputDel: inputDel,
        loadLines: loadLines,
        insLine: insLine,
        LF: LF,
        pars: pars,
        urlUp: urlUp,
        update: update,
        suggestInit: suggestInit,
    }
}();

(function() {

    /**
     * Double slider for dates
     */
    const Bislide = function() {
        function init() {
            // Initialize Sliders
            let els = document.getElementsByClassName("bislide");
            for (let x = 0; x < els.length; x++) {
                let sliders = els[x].getElementsByTagName("input");
                let slider1;
                let slider2;
                for (let y = 0; y < sliders.length; y++) {
                    if (sliders[y].type !== "range") continue;
                    if (!slider1) {
                        slider1 = sliders[y];
                        continue;
                    }
                    slider2 = sliders[y];
                    break;
                }
                if (!slider2) continue;
                els[x].values = els[x].getElementsByClassName("values")[0];
                els[x].slider1 = slider1;
                els[x].slider1.oninput = Bislide.input;
                els[x].slider1.onchange = Bislide.change;
                els[x].slider2 = slider2;
                els[x].slider2.oninput = Bislide.input;
                els[x].slider2.onchange = Bislide.change;
                slider2.oninput();
            }
        }

        function input() {
            // Get slider values
            var parent = this.parentNode;
            var val1 = parseFloat(parent.slider1.value);
            var val2 = parseFloat(parent.slider2.value);
            // swap value if needed 
            if (val1 > val2) {
                parent.slider1.value = val2;
                parent.slider2.value = val1;
            }
            // display
            if (!parent.values) return;
            parent.values.innerHTML = parent.slider1.value + " – " + parent.slider2.value;
        }
        /**
         * default action on change, submit form
         */
        function change(e) {
            const input = e.target;
            // submit form
            input.form.dispatchEvent(new Event('submit', { "bubbles": true, "cancelable": true }));
        }
        return {
            init: init,
            input: input,
            change: change,
        }
    }();
    // bottom script
    Bislide.init();
    const Medict = function() {
        function init() {
            // init the form
            const form = document.forms['medict'];
            if (!form) return;
            Formajax.init(form);
            Formajax.divSetup('index');
            Formajax.update(false); // no entry in history
            // prevent submit befor afect it as event
            form.addEventListener('submit', (e) => {
                e.preventDefault();
                Formajax.update(true);
                return false;
            }, true);
            form.q.addEventListener('input', (e) => {
                form.dispatchEvent(new Event('submit', { "bubbles": true, "cancelable": true }));
            }, true);
            // for efficiency, put a click event on the terme container (not all termes)
            const index = document.getElementById('index');
            index.addEventListener('click', indexClick);
        }
        /**
         * Hilite selected terms in nomenclatura column
         */
        function indexClick(e) {
            e.preventDefault();
            // catch a link inside column of terms
            const a = e.target;
            if (a.tagName.toLowerCase() != 'a') return;

            if (!a.hash) return;
            const div = document.getElementById('entrees');
            if (div.loading) return; // still loading
            div.loading = true;


            let entry = a.hash.substr(1);
            const query = '?' + Formajax.pars('an1', 'an2') + "&t=" + entry;

            div.innerText = '';
            let url = div.dataset.url + query;
            Formajax.loadLines(url, function(html) {
                Formajax.insLine(div, html);
            }, Formajax.LF);

            // change class
            if (document.lastA) document.lastA.classList.remove('active');
            if (a.classList.contains("active")) {
                a.classList.remove('active');
            } else {
                document.lastA = a;
                a.classList.add('active');
            }
        }
        return {
            init: init,
        }
    }();
    Medict.init();
})();


/** Load file as bottom script */
/* unplug
var bottom_titre = function() {
    if (!bottom_titre.checks) {
        bottom_titre.checks = document.querySelectorAll(".dico_check");
    }
    for (var i = 0, len = bottom_titre.checks.length; i < len; i++) {
        var input = bottom_titre.checks[i];
        input.addEventListener('click', function(evt) {
            if (this.checked) {
                this.parentNode.classList.add("checked");
            } else {
                this.parentNode.classList.remove("checked");
            }
        });
    }
    var el;
    el = document.getElementById("dico_checkall");
    if (el) {
        span = el.parentNode.getElementsByTagName('span')[0];
        console.log(span);
        el.addEventListener('click', function(evt) {
            var checked = this.checked;
            if (checked) {
                span.old = span.innerText;
                span.innerText = "Tout décocher";
            } else {
                span.innerText = "Tout sélectionner";
            }
            for (var i = 0, len = bottom_titre.checks.length; i < len; i++) {
                if (bottom_titre.checks[i].offsetParent === null) continue; // invisible
                bottom_titre.checks[i].checked = checked;
                if (checked) {
                    bottom_titre.checks[i].parentNode.classList.add("checked");
                } else {
                    bottom_titre.checks[i].parentNode.classList.remove("checked");
                }
            }
        });
    }

}
bottom_titre();
*/
/*
window.addEventListener('beforeunload', (event) => {
    event.returnValue = "Le formulaire n’a pas été enregistré, êtes vous certain de quitter ?";
});
*/