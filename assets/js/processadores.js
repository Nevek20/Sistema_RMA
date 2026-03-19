// ================================================
// SENHA / MODO EDIÇÃO
// ================================================

let modoEdicao = false;

function abrirModalSenha() {
    if (modoEdicao) { desativarModoEdicao(); return; }
    document.getElementById('input-senha').value = '';
    document.getElementById('senha-erro').style.display = 'none';
    document.getElementById('modal-senha').classList.add('ativo');
    setTimeout(() => document.getElementById('input-senha').focus(), 100);
}

function fecharModalSenha() {
    document.getElementById('modal-senha').classList.remove('ativo');
}

function confirmarSenha() {
    if (document.getElementById('input-senha').value === ADMIN_PASS) {
        fecharModalSenha();
        ativarModoEdicao();
    } else {
        document.getElementById('senha-erro').style.display = 'block';
        document.getElementById('input-senha').value = '';
        document.getElementById('input-senha').focus();
    }
}

function ativarModoEdicao() {
    modoEdicao = true;
    document.body.classList.add('modo-edicao');
    const btn = document.getElementById('btn-lock');
    btn.textContent = '🔓';
    btn.title = 'Sair do modo de edição';
}

function desativarModoEdicao() {
    modoEdicao = false;
    document.body.classList.remove('modo-edicao');
    document.querySelectorAll('.row-check').forEach(cb => cb.checked = false);
    const ca = document.getElementById('check-all');
    if (ca) ca.checked = false;
    atualizarBarra();
    const btn = document.getElementById('btn-lock');
    btn.textContent = '🔒';
    btn.title = 'Modo de edição';
}

// ================================================
// CHECKBOXES + BARRA
// ================================================

function toggleAll(source) {
    document.querySelectorAll('.row-check').forEach(cb => cb.checked = source.checked);
    atualizarBarra();
}

function atualizarBarra() {
    const sel = document.querySelectorAll('.row-check:checked');
    const barra = document.getElementById('barra-acoes');
    const btnEditar = document.getElementById('btn-editar-sel');
    if (sel.length > 0) {
        barra.classList.add('ativo');
        document.getElementById('barra-contagem').textContent = sel.length + ' selecionado(s)';
        btnEditar.disabled = sel.length !== 1;
        btnEditar.title = sel.length !== 1 ? 'Selecione apenas 1 para editar' : '';
    } else {
        barra.classList.remove('ativo');
    }
}

// ================================================
// MODAL EDITAR
// ================================================

function abrirModalEditar() {
    const row = document.querySelector('.row-check:checked')?.closest('tr');
    const modal = document.getElementById('modal-editar');
    if (!row || !modal) return;

    // Preenche campos comuns
    const editId = document.getElementById('edit-id');
    if (editId) editId.value = row.dataset.id;

    // Campos por página
    const editNome    = document.getElementById('edit-nome');
    const editModelo  = document.getElementById('edit-modelo');
    const editSn      = document.getElementById('edit-sn');
    const editProc    = document.getElementById('edit-processador');
    const editCliente = document.getElementById('edit-cliente');

    if (editNome)    editNome.value    = row.dataset.nome    || '';
    if (editModelo)  editModelo.value  = row.dataset.modelo  || '';
    if (editSn)      editSn.value      = row.dataset.sn      || '';
    if (editProc)    editProc.value    = row.dataset.processadorId || '';
    if (editCliente) editCliente.value = row.dataset.clienteId    || '';

    modal.classList.add('ativo');
    setTimeout(() => modal.querySelector('input, select')?.focus(), 100);
}

function fecharModalEditar() {
    document.getElementById('modal-editar')?.classList.remove('ativo');
}

// ================================================
// EXCLUIR
// ================================================

function confirmarExcluir() {
    const sel = document.querySelectorAll('.row-check:checked');
    if (!sel.length) return;
    if (!confirm(`Excluir ${sel.length} item(ns) selecionado(s)?`)) return;
    const ids = Array.from(sel).map(cb => cb.closest('tr').dataset.id).join(',');
    document.getElementById('excluir-ids').value = ids;
    document.getElementById('form-excluir').submit();
}

// ================================================
// FECHAR MODAL AO CLICAR NO FUNDO / ESC
// ================================================

document.addEventListener('click', e => {
    if (e.target.id === 'modal-senha')  fecharModalSenha();
    if (e.target.id === 'modal-editar') fecharModalEditar();
});

document.addEventListener('keydown', e => {
    if (e.key === 'Escape') { fecharModalSenha(); fecharModalEditar(); }
    if (e.key === 'Enter' && document.getElementById('modal-senha')?.classList.contains('ativo'))
        confirmarSenha();
});

// ================================================
// MÚLTIPLOS PROCESSADORES (vincular)
// ================================================

function atualizarBotoes() {
    const items = document.querySelectorAll('.item-processador');
    items.forEach((item, index) => {
        item.querySelector('.btn-plus')?.remove();
        item.querySelector('.btn-minus')?.remove();
        const linhaTopo = item.querySelector('.linha-topo');

        if (index === items.length - 1) {
            const plus = document.createElement('button');
            plus.type = 'button'; plus.className = 'btn-plus';
            plus.innerText = '+'; plus.onclick = addProcessador;
            linhaTopo.appendChild(plus);
        }
        if (items.length > 1 && index > 0) {
            const minus = document.createElement('button');
            minus.type = 'button'; minus.className = 'btn-minus';
            minus.innerText = '-';
            minus.onclick = () => { item.remove(); atualizarBotoes(); };
            linhaTopo.appendChild(minus);
        }
    });
}

function addProcessador() {
    const container = document.getElementById('processadores-container');
    if (!container) return;
    const items = container.querySelectorAll('.item-processador');
    const clone = items[items.length - 1].cloneNode(true);
    clone.querySelectorAll('input').forEach(i => i.value = '');
    clone.querySelectorAll('select').forEach(s => s.selectedIndex = 0);
    container.appendChild(clone);
    atualizarBotoes();
}

// ================================================
// BACKUP — confirmar sem onsubmit inline
// ================================================

document.addEventListener('DOMContentLoaded', () => {
    document.getElementById('formBackup')?.addEventListener('submit', e => {
        if (!confirm('Deseja realmente fazer o backup?')) e.preventDefault();
    });
    atualizarBotoes();
});