(() => {
    const menuButton = document.querySelector('[data-menu]');
    const sidebar = document.querySelector('#sidebar');

    menuButton?.addEventListener('click', () => {
        sidebar?.classList.toggle('open');
    });

    document.querySelectorAll('[data-mask="cnpj"]').forEach((input) => {
        input.addEventListener('input', () => {
            let v = input.value.replace(/\D/g, '').slice(0, 14);
            v = v.replace(/^(\d{2})(\d)/, '$1.$2')
                 .replace(/^(\d{2})\.(\d{3})(\d)/, '$1.$2.$3')
                 .replace(/\.(\d{3})(\d)/, '.$1/$2')
                 .replace(/(\d{4})(\d)/, '$1-$2');
            input.value = v;
        });
    });

    document.querySelectorAll('[data-mask="cep"]').forEach((input) => {
        input.addEventListener('input', () => {
            let v = input.value.replace(/\D/g, '').slice(0, 8);
            input.value = v.replace(/(\d{5})(\d)/, '$1-$2');
        });
    });

    const validarCnpj = (valor) => {
        const cnpj = valor.replace(/\D/g, '');
        if (cnpj.length !== 14 || /^(\d)\1{13}$/.test(cnpj)) return false;
        const calc=(base,pesos)=>{const soma=pesos.reduce((t,p,i)=>t+Number(base[i])*p,0);const r=soma%11;return r<2?0:11-r;};
        const d1=calc(cnpj.slice(0,12),[5,4,3,2,9,8,7,6,5,4,3,2]);
        const d2=calc(cnpj.slice(0,12)+d1,[6,5,4,3,2,9,8,7,6,5,4,3,2]);
        return cnpj.endsWith(`${d1}${d2}`);
    };
    document.querySelectorAll('[data-validate-cnpj]').forEach((input)=>{
        const fb=input.parentElement?.querySelector('[data-cnpj-feedback]');
        const update=()=>{const vazio=!input.value.trim();const ok=!vazio&&validarCnpj(input.value);
            input.classList.toggle('input-valid',ok);input.classList.toggle('input-invalid',!vazio&&!ok);
            if(fb){fb.textContent=ok?'CNPJ válido.':'Informe um CNPJ válido.';fb.classList.toggle('valid',ok);fb.classList.toggle('invalid',!vazio&&!ok);}
            input.setCustomValidity(vazio||ok?'':'CNPJ inválido');
        };
        input.addEventListener('input',update);input.addEventListener('blur',update);update();
    });
})();

