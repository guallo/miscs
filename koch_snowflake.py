#!/usr/bin/env python3

import math
import random

from matplotlib import pyplot


def visualize(x_ords, y_ords, xlabel, ylabel, title):
    fig, axes = pyplot.subplots(figsize=(7, 5))
    axes.plot(x_ords, y_ords)
    axes.set(xlabel=xlabel, ylabel=ylabel, title=title)
    pyplot.show()

def koch_snowflake(d=10, loops=4, outward=True):
    points = [(-d, 0), (d, 0), next_equidistant_point(-d, 0, d, 0)]
    
    for l in range(loops):
        i = 0
        while i < len(points):
            p1 = points[i - 1]
            p2 = points[i]
            s1, s2, s3 = split_segment_n_parts(*p1, *p2, 3)
            
            if outward:
                nep = next_equidistant_point(*s3[0], *s1[1])
            else:
                nep = next_equidistant_point(*s1[1], *s3[0])

            points = [*points[:i], s1[1], nep, s3[0], *points[i:]]
            i += 4

    points += [points[0]]
    return tuple(points)

def split_segment_n_parts(x1, y1, x2, y2, n):
    assert n >= 1
    sd = distance(x1, y1, x2, y2)
    d = sd / n
    segments = split_segment_by_distance(x1, y1, x2, y2, d, n - 1)
    lsegment = segments[-1]
    lsegment = (lsegment[0], (x2, y2))  # correct last point of last segment
    return segments[:-1] + (lsegment,)

def split_segment_by_distance(x1, y1, x2, y2, d, max_split=None):
    assert d > 0
    assert max_split is None or max_split >= 0

    if (x1 == x2 and y1 == y2):
        return (((x1, y1), (x2, y2)),)

    segments = []
    splits = 0
    x, y = x1, y1
    degrees = degrees_x_axis(x1, y1, x2, y2)
    
    while (max_split is None or splits < max_split) \
            and d < distance(x, y, x2, y2):
        s = segment(x, y, degrees, d)
        segments.append(s)
        x, y = s[-1]
        splits += 1

    segments.append(((x, y), (x2, y2)))

    if len(segments) > math.ceil(distance(x1, y1, x2, y2) / d):
        segments = segments[:-1]  # protect against precision issues

    lsegment = segments[-1]
    lsegment = (lsegment[0], (x2, y2))  # correct last point of last segment
    return tuple(segments[:-1]) + (lsegment,)

def segment(x1, y1, degrees, d):
    assert d >= 0
    degrees = norm_degrees(degrees)
    osegment = segment_from_origin(degrees, d)
    segment = translate(x1, y1, osegment)
    return segment

def segment_from_origin(degrees, d):
    assert d >= 0
    degrees = norm_degrees(degrees)
    ca, co = catetos_with_sign(d, degrees)
    osegment = ((0, 0), (ca, co))
    return osegment

def next_equidistant_point(x1, y1, x2, y2):
    # clock wise
    [rpoint] = rotate(-60, x1, y1, [(x2, y2)])
    return rpoint

def rotate(degrees, x, y, points):
    # counter clock wise
    degrees = norm_degrees(degrees)
    otpoints = translate(-x, -y, points)
    orpoints = rotate_origin(degrees, otpoints)
    rpoints = translate(x, y, orpoints)
    return rpoints

def rotate_origin(degrees, points):
    # counter clock wise
    degrees = norm_degrees(degrees)
    rpoints = []
    for x, y in points:
        odegrees = degrees_origin_x_axis(x, y)
        target_degrees = norm_degrees(odegrees + degrees)
        h = hypotenuse(x, y)
        rx = math.cos(math.radians(target_degrees)) * h
        ry = math.sin(math.radians(target_degrees)) * h
        rpoints.append((rx, ry))
    return tuple(rpoints)

def degrees_x_axis(x1, y1, x2, y2):
    # counter clock wise
    [(otx2, oty2)] = translate(-x1, -y1, [(x2, y2)])
    degrees = degrees_origin_x_axis(otx2, oty2)
    return degrees

def degrees_origin_x_axis(x, y):
    # counter clock wise
    assert x != 0 or y != 0
    
    if x == 0:
        if y > 0:
            return 90
        return 270
    
    d = math.degrees(math.atan(y / x))
    q = quadrant_coords(x, y)
    
    if q == 1:
        return d
    if q == 4:
        return 360 + d
    return 180 + d

def quadrant_coords(x, y):
    if x == 0:
        if y == 0:
            return 1
        if y > 0:
            return 2
        return 4
    if x > 0:
        if y >= 0:
            return 1
        return 4
    if y <= 0:
        return 3
    return 2

def norm_degrees(degrees):
    return degrees % 360

def translate(dx, dy, points):
    tpoints = []
    for x, y in points:
        tpoints.append((x + dx, y + dy))
    return tuple(tpoints)

def distance(x1, y1, x2, y2):
    return hypotenuse(x1 - x2, y1 - y2)

def hypotenuse(c1, c2):
    return (c1 ** 2 + c2 ** 2) ** 0.5

def cateto(h, c):
    return (h ** 2 - c ** 2) ** 0.5

def cateto_opposite_with_sign(h, degrees):
    assert h >= 0
    degrees = norm_degrees(degrees)
    co = math.sin(math.radians(degrees)) * h
    return co

def cateto_adjacent_with_sign(h, degrees):
    assert h >= 0
    degrees = norm_degrees(degrees)
    ca = math.cos(math.radians(degrees)) * h
    return ca

def catetos_with_sign(h, degrees):
    ca = cateto_adjacent_with_sign(h, degrees)
    co = cateto_opposite_with_sign(h, degrees)
    return (ca, co)


d = 10
loops = 4
outward = True
points = koch_snowflake(d, loops, outward)
visualize([x for x, y in points], [y for x, y in points], 'x', 'y', 
    f'koch_snowflake(d={d}, loops={loops}, outward={outward}) -> {len(points)} points')
