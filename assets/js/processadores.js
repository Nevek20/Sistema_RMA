function atualizarBotoes() {
    const items = document.querySelectorAll('.item-processador');

    items.forEach((item, index) => {

        const btnPlus = item.querySelector('.btn-plus');
        const btnMinus = item.querySelector('.btn-minus');
        if (btnPlus) btnPlus.remove();
        if (btnMinus) btnMinus.remove();

        const linhaTopo = item.querySelector('.linha-topo');

        if (index === items.length - 1) {
            const plus = document.createElement('button');
            plus.type = 'button';
            plus.className = 'btn-plus';
            plus.innerText = '+';
            plus.onclick = addProcessador;
            linhaTopo.appendChild(plus);
        }

        if (items.length > 1 && index > 0) {
            const minus = document.createElement('button');
            minus.type = 'button';
            minus.className = 'btn-minus';
            minus.innerText = '-';
            minus.onclick = function () {
                item.remove();
                atualizarBotoes();
            };
            linhaTopo.appendChild(minus);
        }
    });
}

function addProcessador() {
    const container = document.getElementById('processadores-container');
    const items = container.querySelectorAll('.item-processador');
    const lastItem = items[items.length - 1];

    const clone = lastItem.cloneNode(true);

    clone.querySelectorAll('input').forEach(input => input.value = '');
    clone.querySelectorAll('select').forEach(select => select.selectedIndex = 0);

    container.appendChild(clone);

    atualizarBotoes();
}

document.addEventListener('DOMContentLoaded', atualizarBotoes);