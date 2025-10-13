<script>
    const getCurrentTranslation = {!! json_encode(getCurrentTranslation()) !!};
    
    var Toast = Swal.mixin({
      toast: true,
      position: 'top-end',
      showConfirmButton: false,
      timer: 3000,
      didOpen: (toast) => {
            toast.addEventListener('click', Swal.close);
        }
    });

    @if(Session::has('message'))
        Toast.fire({
            icon: 'success',
            title: "{{ session('message') }}"
        });
    @endif

    @if(Session::has('success'))
        Toast.fire({
            icon: 'success',
            title: "{{ session('success') }}"
        });

        //Swal.fire('Success', '{{ session('success') }}');
    @endif

    @if(Session::has('error'))
        Toast.fire({
            icon: 'error',
            title: "{{ session('error') }}"
        });
        //Swal.fire('Warning', '{{ session('error') }}');
    @endif

    @if(Session::has('info'))
        Toast.fire({
            icon: 'info',
            title: "{{ session('info') }}"
        });
    @endif

    @if(Session::has('warning'))
        Toast.fire({
            icon: 'warning',
            title: "{{ session('warning') }}"
        });
    @endif
</script>
