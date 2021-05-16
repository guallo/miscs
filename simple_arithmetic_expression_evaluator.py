#!/usr/bin/env python3

import re
import operator

white_regex = r'\s'
signs_regex = r'(?P<signs>[+-]+)'
paren_regex = r'\((?P<expr>[^()]+)\)'
num_regex = r'[+-]?\d+(\.\d+)?'
bin_op_regex = fr'(?P<left>{num_regex})(?P<op>[{{ops}}])(?P<right>{num_regex})'

op = {
    '*': operator.mul,
    '/': operator.truediv,
    '+': operator.add,
    '-': operator.sub
}

def eval_(expr):
    while re.search(paren_regex, expr):
        expr = re.sub(paren_regex, lambda m: str(eval_(m['expr'])), expr)
    expr = re.sub(white_regex, '', expr)
    expr = re.sub(signs_regex, lambda m: '+-'[m['signs'].count('-') % 2], expr)
    for ops in (r'*/', r'+-'):
        regex = bin_op_regex.format(ops=ops)
        while re.search(regex, expr):
            expr = re.sub(regex,
                lambda m: str(op[m['op']](float(m['left']), float(m['right']))),
                expr, 1)
    float_res = float(expr)
    int_res = int(float_res)
    return int_res if int_res == float_res else float_res

if __name__ == '__main__':
    while expr := input('expr: '):
        print(eval_(expr))
