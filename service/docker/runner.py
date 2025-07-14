#!/usr/bin/env python3
# runner.py

import sys
import json
from RestrictedPython import compile_restricted, safe_builtins
from RestrictedPython.Guards import (
    safer_getattr,
    full_write_guard,
    guarded_iter_unpack_sequence,
)
from RestrictedPython.Eval import (
    default_guarded_getiter,
    default_guarded_getitem,
)

class DirectPrinter:
    def __init__(self, *args, **kwargs):
        pass

    def _call_print(self, args, kwargs=None):
        if kwargs is None:
            kwargs = {}
        if not isinstance(args, (tuple, list)):
            args = (args,)
        print(*args, **kwargs)

with open('solution.py', 'r') as f:
    src = f.read()

glb = {
    '__builtins__':          safe_builtins,             
    '_getattr_':             safer_getattr,             
    '_getitem_':             default_guarded_getitem,   
    '_getiter_':             default_guarded_getiter,   
    '_iter_unpack_sequence_':guarded_iter_unpack_sequence, 
    '_write_':               full_write_guard,          
    '_print_':               DirectPrinter,            
}

glb['__builtins__'].update({
    'input': input,
    'len':   len,
    'int':   int,
    'str':   str,
})

try:
    import warnings
    warnings.filterwarnings(
        "ignore",
        message=".*never reads 'printed' variable.*",
        category=SyntaxWarning
    )
    byte_code = compile_restricted(src, '<string>', 'exec')
    exec(byte_code, glb, None)

    printed = glb.get('printed')
    if printed:
        sys.stdout.write(printed)

except Exception as e:
    sys.stderr.write(f"ERROR: {e}")
    sys.exit(1)
