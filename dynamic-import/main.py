from pkgutil import iter_modules
from importlib import import_module


class Factory:
    @staticmethod
    def get_instance(pkg_name, cls_prefix, cls_suffix, params):
        cls_list = list(
            filter(None,
                map(lambda m: getattr(m, f'{cls_prefix.capitalize()}{cls_suffix}', None),
                    map(import_module,
                        [i.name for i in iter_modules([pkg_name.replace('.', '/')], f'{pkg_name}.') if not i.ispkg]))))
        cls = cls_list and cls_list[0] or None
        return cls and cls(**params) or None


Factory.get_instance('models.classes', 'b', 'Runnable', {'name': 'Jose', 'height': 65})
